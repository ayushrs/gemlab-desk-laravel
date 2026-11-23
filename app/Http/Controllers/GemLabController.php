<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog,Customer,Order,Sample,Test,Report,Payment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Hash,Storage};
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class GemLabController extends Controller
{
    private function writeAudit(string $action,string $entity,?int $id=null,string $details=''):void {
        AuditLog::create(['user_id'=>Auth::id(),'username'=>Auth::user()?->username ?? 'public','action'=>$action,'entity'=>$entity,'entity_id'=>$id,'details'=>$details]);
    }
    private function nextCode(string $prefix,string $model):string {
        $last=$model::query()->lockForUpdate()->count()+1;
        return $prefix.'-'.str_pad((string)$last,5,'0',STR_PAD_LEFT);
    }
    private function allowed(array $roles):bool { return Auth::check() && (Auth::user()->role==='Admin' || in_array(Auth::user()->role,$roles,true)); }

    public function portal(){return view('portal.index');}
    public function loginForm(){return view('auth.login');}
    public function login(Request $request){
        $data=$request->validate(['username'=>'required|string','password'=>'required|string']);
        if(Auth::attempt(['username'=>$data['username'],'password'=>$data['password']])){$request->session()->regenerate();return redirect()->intended('/staff');}
        return back()->withErrors(['username'=>'Incorrect username or password.'])->onlyInput('username');
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/login');}
    public function staff(){return view('staff.index');}
    public function me(){return response()->json(['id'=>Auth::id(),'username'=>Auth::user()->username,'role'=>Auth::user()->role,'name'=>Auth::user()->name]);}

    public function publicRequest(Request $request){
        $d=$request->validate(['name'=>'required|string|max:160','phone'=>'required|string|min:8|max:18','service'=>'required|string|max:160','description'=>'required|string|max:2000','email'=>'nullable|email|max:190','company'=>'nullable|string|max:190','address'=>'nullable|string|max:1000','gemstone_type'=>'nullable|string|max:120','colour'=>'nullable|string|max:120','weight'=>'nullable|numeric|min:0','pieces'=>'nullable|integer|min:1|max:10000','condition_note'=>'nullable|string|max:2000','notes'=>'nullable|string|max:3000']);
        $result=DB::transaction(function()use($d){
            $customer=Customer::where('phone',$d['phone'])->first();
            if($customer){$customer->update(['name'=>$d['name'],'email'=>$d['email']??$customer->email,'company'=>$d['company']??$customer->company]);}
            else {$customer=Customer::create(['code'=>$this->nextCode('CUS',Customer::class),'name'=>$d['name'],'company'=>$d['company']??null,'email'=>$d['email']??null,'phone'=>$d['phone'],'address'=>$d['address']??null]);}
            $order=Order::create(['order_no'=>$this->nextCode('GL',Order::class),'customer_id'=>$customer->id,'service'=>$d['service'],'priority'=>'Regular','status'=>'Enquiry Received','payment_status'=>'Pending','amount'=>0,'notes'=>$d['notes']??null,'received_at'=>now()]);
            $sample=Sample::create(['order_id'=>$order->id,'sample_no'=>$this->nextCode('SMP',Sample::class),'description'=>$d['description'],'gemstone_type'=>$d['gemstone_type']??null,'colour'=>$d['colour']??null,'weight'=>$d['weight']??null,'pieces'=>$d['pieces']??1,'condition_note'=>$d['condition_note']??null,'status'=>'Enquiry']);
            return ['order_no'=>$order->order_no,'sample_no'=>$sample->sample_no];
        });
        $this->writeAudit('Public request received','Order',null,$result['order_no']);
        return response()->json(['success'=>true,...$result,'message'=>'Your request has been received. Save your order number to track its progress.'],201);
    }
    public function publicTrack(Request $request){
        $d=$request->validate(['order_no'=>'required|string|max:40','phone'=>'required|string|max:18']);
        $order=Order::with(['customer','samples.reports'=>fn($q)=>$q->where('status','Issued')])->where('order_no',strtoupper(trim($d['order_no'])))->whereHas('customer',fn($q)=>$q->where('phone',$d['phone']))->first();
        if(!$order)return response()->json(['error'=>'We could not find an order matching those details.'],404);
        return response()->json(['order'=>['order_no'=>$order->order_no,'service'=>$order->service,'status'=>$order->status,'payment_status'=>$order->payment_status,'created_at'=>$order->created_at,'customer'=>$order->customer->name,'phone'=>$order->customer->phone],'samples'=>$order->samples->map(fn($s)=>['sample_no'=>$s->sample_no,'description'=>$s->description,'gemstone_type'=>$s->gemstone_type,'colour'=>$s->colour,'weight'=>$s->weight,'status'=>$s->status,'report_no'=>$s->reports->first()?->report_no,'issued_at'=>$s->reports->first()?->issued_at])]);
    }
    public function dashboard(){return response()->json(['customers'=>Customer::count(),'orders'=>Order::count(),'pending'=>Order::whereNotIn('status',['Dispatched','Delivered'])->count(),'reports'=>Report::count(),'revenue'=>(float)Payment::sum('amount'),'recent'=>Order::with('customer')->latest()->limit(8)->get()->map(fn($o)=>['id'=>$o->id,'order_no'=>$o->order_no,'service'=>$o->service,'status'=>$o->status,'priority'=>$o->priority,'created_at'=>$o->created_at,'customer'=>$o->customer->name])]);}
    public function customers(Request $request){
        if($request->isMethod('get'))return Customer::latest()->get();
        $d=$request->validate(['name'=>'required|string|max:160','company'=>'nullable|string|max:190','email'=>'nullable|email|max:190','phone'=>'nullable|string|max:30','gst'=>'nullable|string|max:30','address'=>'nullable|string|max:1000']);
        $customer=DB::transaction(fn()=>Customer::create(['code'=>$this->nextCode('CUS',Customer::class),...$d]));$this->writeAudit('Created','Customer',$customer->id,$customer->name);return response()->json(['id'=>$customer->id,'code'=>$customer->code],201);
    }
    public function orders(Request $request){
        if($request->isMethod('get'))return Order::with('customer')->latest()->get()->map(function($o){$row=$o->toArray();$row['customer']=$o->customer->name;return $row;});
        $d=$request->validate(['customer_id'=>'required|exists:customers,id','service'=>'required|string|max:190','priority'=>'nullable|in:Regular,Urgent','amount'=>'nullable|numeric|min:0','payment_status'=>'nullable|in:Pending,Paid','notes'=>'nullable|string|max:3000']);
        $order=DB::transaction(fn()=>Order::create(['order_no'=>$this->nextCode('GL',Order::class),'customer_id'=>$d['customer_id'],'service'=>$d['service'],'priority'=>$d['priority']??'Regular','status'=>'Received','payment_status'=>$d['payment_status']??'Pending','amount'=>$d['amount']??0,'notes'=>$d['notes']??null,'received_at'=>now()]));$this->writeAudit('Created','Order',$order->id,$order->order_no);return response()->json(['id'=>$order->id,'order_no'=>$order->order_no],201);
    }
    public function samples(Request $request){
        if($request->isMethod('get'))return Sample::with('order')->latest()->get()->map(fn($s)=>$s->toArray()+['order_no'=>$s->order->order_no]);
        $d=$request->validate(['order_id'=>'required|exists:orders,id','description'=>'required|string|max:500','gemstone_type'=>'nullable|string|max:120','colour'=>'nullable|string|max:120','weight'=>'nullable|numeric|min:0','pieces'=>'nullable|integer|min:1','condition_note'=>'nullable|string|max:2000']);
        $sample=DB::transaction(fn()=>Sample::create(['sample_no'=>$this->nextCode('SMP',Sample::class),...$d,'status'=>'Received']));$this->writeAudit('Registered','Sample',$sample->id,$sample->sample_no);return response()->json(['id'=>$sample->id,'sample_no'=>$sample->sample_no],201);
    }
    public function tests(Request $request){
        if($request->isMethod('get'))return Test::with('sample')->latest()->get()->map(fn($t)=>$t->toArray()+['sample_no'=>$t->sample->sample_no,'description'=>$t->sample->description]);
        $d=$request->validate(['sample_id'=>'required|exists:samples,id','method'=>'required|string|max:190','observations'=>'nullable|string|max:5000','result'=>'required|string|max:5000']);
        $test=DB::transaction(function()use($d){$test=Test::create([...$d,'tested_by'=>Auth::user()->name,'tested_at'=>now()]);Sample::whereKey($d['sample_id'])->update(['status'=>'Testing']);return $test;});$this->writeAudit('Recorded test','Test',$test->id,$test->method);return response()->json(['id'=>$test->id],201);
    }
    public function lookups(){return response()->json(['customers'=>Customer::select('id','name','code')->orderBy('name')->get(),'orders'=>Order::select('id','order_no')->latest()->get(),'samples'=>Sample::select('id','sample_no','description')->latest()->get()]);}
    public function setStatus(Request $request,int $id){if(!$this->allowed(['Reception','Report Manager','Dispatch']))return response()->json(['error'=>'Insufficient permission'],403);$d=$request->validate(['status'=>'required|in:Enquiry Received,Received,Registered,Payment Pending,Ready for Testing,Testing,Review,Approved,Report Generated,Dispatched,Delivered,On Hold']);$o=Order::findOrFail($id);$o->update($d);$this->writeAudit('Status changed','Order',$id,$d['status']);return response()->json(['success'=>true]);}
    public function payment(Request $request,int $id){if(!$this->allowed(['Accounts']))return response()->json(['error'=>'Insufficient permission'],403);$d=$request->validate(['amount'=>'required|numeric|min:0.01','mode'=>'nullable|string|max:80','reference'=>'nullable|string|max:190']);$o=Order::findOrFail($id);$p=DB::transaction(function()use($d,$o){$p=Payment::create(['order_id'=>$o->id,'amount'=>$d['amount'],'mode'=>$d['mode']??'Cash','reference'=>$d['reference']??null,'paid_at'=>now()]);$paid=(float)$o->payments()->sum('amount');$o->update(['payment_status'=>$paid >= (float)$o->amount ? 'Paid':'Partially Paid']);return $paid;});$this->writeAudit('Payment received','Order',$id,(string)$d['amount']);return response()->json(['success'=>true,'paid'=>$p]);}
    public function issue(Request $request,int $id){if(!$this->allowed(['Senior Gemmologist','Report Manager']))return response()->json(['error'=>'Insufficient permission'],403);$d=$request->validate(['conclusion'=>'nullable|string|max:5000','treatment'=>'nullable|string|max:190','origin'=>'nullable|string|max:190']);$sample=Sample::with('tests')->findOrFail($id);$test=$sample->tests()->latest()->first();if(!$test)return response()->json(['error'=>'Record at least one laboratory test before issuing a report'],422);$report=DB::transaction(function()use($d,$sample,$test){$r=Report::create(['sample_id'=>$sample->id,'report_no'=>$this->nextCode('GTL',Report::class),'conclusion'=>$d['conclusion']??$test->result,'treatment'=>$d['treatment']??'Not determined','origin'=>$d['origin']??'Not determined','issued_at'=>now(),'approved_by'=>Auth::user()->name,'status'=>'Issued']);$sample->update(['status'=>'Reported']);return $r;});$this->writeAudit('Issued report','Report',$report->id,$report->report_no);return response()->json(['id'=>$report->id,'report_no'=>$report->report_no],201);}
    public function reports(){return Report::with('sample.order.customer')->latest()->get()->map(function($r){$s=$r->sample;$o=$s->order;return $r->toArray()+['sample_no'=>$s->sample_no,'description'=>$s->description,'order_no'=>$o->order_no,'customer'=>$o->customer->name];});}
    public function verify(string $reportNo){$r=Report::with('sample')->where('report_no',$reportNo)->first();if(!$r)return response()->json(['valid'=>false,'message'=>'No matching certificate was found'],404);$s=$r->sample;return response()->json(['valid'=>$r->status==='Issued','report'=>['report_no'=>$r->report_no,'conclusion'=>$r->conclusion,'treatment'=>$r->treatment,'origin'=>$r->origin,'issued_at'=>$r->issued_at,'status'=>$r->status,'sample_no'=>$s->sample_no,'description'=>$s->description,'gemstone_type'=>$s->gemstone_type,'colour'=>$s->colour,'weight'=>$s->weight]]);}
    public function verifyPage(){return view('portal.verify');}
    public function pdf(int $id){$r=Report::with('sample.order.customer')->findOrFail($id);if($r->status!=='Issued')abort(404);return $this->makePdf($r);}
    public function publicPdf(string $reportNo){$r=Report::with('sample.order.customer')->where('report_no',$reportNo)->where('status','Issued')->firstOrFail();return $this->makePdf($r);}
    private function makePdf(Report $r){$s=$r->sample;$o=$s->order;$c=$o->customer;$verifyUrl=url('/verify?report='.$r->report_no);$qr=Builder::create()->writer(new PngWriter())->data($verifyUrl)->encoding(new Encoding('UTF-8'))->errorCorrectionLevel(ErrorCorrectionLevel::Medium)->size(180)->margin(8)->build();$qrData='data:image/png;base64,'.base64_encode($qr->getString());$pdf=Pdf::loadView('pdf.report',compact('r','s','o','c','verifyUrl','qrData'))->setPaper('a4');return $pdf->download($r->report_no.'.pdf');}
    public function audit(){if(!Auth::user()||Auth::user()->role!=='Admin')return response()->json(['error'=>'Insufficient permission'],403);return AuditLog::latest()->limit(100)->get();}
}
