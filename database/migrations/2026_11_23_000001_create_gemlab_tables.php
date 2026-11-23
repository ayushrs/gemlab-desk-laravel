<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users', function(Blueprint $t){$t->id();$t->string('name');$t->string('username')->unique();$t->string('email')->nullable()->unique();$t->string('password');$t->string('role')->default('Reception');$t->rememberToken();$t->timestamps();});
  Schema::create('customers',function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->string('company')->nullable();$t->string('email')->nullable();$t->string('phone',30)->nullable()->index();$t->string('gst')->nullable();$t->text('address')->nullable();$t->timestamps();});
  Schema::create('orders',function(Blueprint $t){$t->id();$t->string('order_no')->unique();$t->foreignId('customer_id')->constrained()->restrictOnDelete();$t->string('service');$t->string('priority')->default('Regular');$t->string('status')->default('Received');$t->string('payment_status')->default('Pending');$t->decimal('amount',12,2)->default(0);$t->text('notes')->nullable();$t->timestamp('received_at')->nullable();$t->timestamps();});
  Schema::create('samples',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->string('sample_no')->unique();$t->string('description');$t->string('gemstone_type')->nullable();$t->string('colour')->nullable();$t->decimal('weight',12,4)->nullable();$t->unsignedInteger('pieces')->default(1);$t->text('condition_note')->nullable();$t->string('status')->default('Received');$t->string('image_path')->nullable();$t->timestamps();});
  Schema::create('tests',function(Blueprint $t){$t->id();$t->foreignId('sample_id')->constrained()->cascadeOnDelete();$t->string('method');$t->text('observations')->nullable();$t->text('result');$t->string('tested_by');$t->timestamp('tested_at')->nullable();$t->timestamps();});
  Schema::create('reports',function(Blueprint $t){$t->id();$t->foreignId('sample_id')->constrained()->restrictOnDelete();$t->string('report_no')->unique();$t->text('conclusion');$t->string('treatment')->nullable();$t->string('origin')->nullable();$t->timestamp('issued_at')->nullable();$t->string('approved_by');$t->string('status')->default('Issued');$t->unsignedInteger('version')->default(1);$t->timestamps();});
  Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->decimal('amount',12,2);$t->string('mode')->default('Cash');$t->string('reference')->nullable();$t->timestamp('paid_at')->nullable();$t->timestamps();});
  Schema::create('shipments',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->string('courier')->nullable();$t->string('tracking_no')->nullable();$t->date('dispatch_date')->nullable();$t->string('delivery_status')->nullable();$t->text('notes')->nullable();$t->timestamps();});
  Schema::create('audit_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('username')->default('system');$t->string('action');$t->string('entity');$t->unsignedBigInteger('entity_id')->nullable();$t->text('details')->nullable();$t->timestamps();});
 }
 public function down(): void {foreach(['audit_logs','shipments','payments','reports','tests','samples','orders','customers','users'] as $table) Schema::dropIfExists($table);}
};
