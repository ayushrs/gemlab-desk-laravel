<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class CreateGemlabAdmin extends Command {
 protected $signature='gemlab:make-admin {username} {email}';
 protected $description='Create or update a GemLab Desk administrator account';
 public function handle(): int {
  $username=$this->argument('username');$email=$this->argument('email');$name=$this->ask('Administrator display name','System Administrator');$password=$this->secret('Set a strong administrator password');
  if(!$password || strlen($password)<12){$this->error('Password must be at least 12 characters.');return self::FAILURE;}
  $user=User::updateOrCreate(['username'=>$username],['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'role'=>'Admin']);
  $this->info('Administrator account is ready: '.$user->username);return self::SUCCESS;
 }
}
