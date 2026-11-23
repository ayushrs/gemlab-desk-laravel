<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GemLabController as G;

Route::get('/',[G::class,'portal'])->name('portal');
Route::get('/login',[G::class,'loginForm'])->middleware('guest')->name('login');
Route::post('/login',[G::class,'login'])->middleware(['guest','throttle:6,1']);
Route::post('/logout',[G::class,'logout'])->middleware('auth')->name('logout');
Route::get('/staff',[G::class,'staff'])->middleware('auth')->name('staff');
Route::get('/verify',[G::class,'verifyPage'])->name('verify.page');
Route::get('/api/verify/{reportNo}',[G::class,'verify'])->where('reportNo','[A-Za-z0-9-]+');
Route::get('/public/reports/{reportNo}.pdf',[G::class,'publicPdf'])->where('reportNo','[A-Za-z0-9-]+');
Route::post('/api/public/request',[G::class,'publicRequest'])->middleware('throttle:10,1');
Route::post('/api/public/track',[G::class,'publicTrack'])->middleware('throttle:20,1');

Route::middleware('auth')->group(function(){
 Route::get('/api/me',[G::class,'me']);
 Route::get('/api/dashboard',[G::class,'dashboard']);
 Route::match(['get','post'],'/api/customers',[G::class,'customers']);
 Route::match(['get','post'],'/api/orders',[G::class,'orders']);
 Route::match(['get','post'],'/api/samples',[G::class,'samples']);
 Route::match(['get','post'],'/api/tests',[G::class,'tests']);
 Route::get('/api/lookups',[G::class,'lookups']);
 Route::post('/api/orders/{id}/status',[G::class,'setStatus']);
 Route::post('/api/orders/{id}/payment',[G::class,'payment']);
 Route::post('/api/samples/{id}/issue',[G::class,'issue']);
 Route::get('/api/reports',[G::class,'reports']);
 Route::get('/api/audit',[G::class,'audit']);
 Route::get('/reports/{id}.pdf',[G::class,'pdf']);
});
