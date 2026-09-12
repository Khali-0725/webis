<?php
namespace Database\Seeders;
use App\Enums\BookingStatus;
use App\Enums\ModerationStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Enums\PricingType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\BookingStatusHistory;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Payment;
use App\Models\ProviderAvailabilityRule;
use App\Models\ProviderPaymentMethod;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceArea;
use App\Models\ProviderSkill;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DemoDataSeeder extends Seeder
{
    private static ?string $pw = null;
    public function run(): void
    {
        self::$pw ??= Hash::make('password123');
        $bg = Barangay::all();
        $cats = ServiceCategory::all();
        if ($bg->isEmpty() || $cats->isEmpty()) { return; }
        $cl = $this->clients($bg);
        $pr = $this->providers($bg);
        $sv = $this->services($pr, $cats);
        $bk = $this->bookings($cl, $pr, $sv, $bg);
        $this->reviews($bk);
        $this->payments($bk);
        $this->convos($bk, $pr);
        $this->settings();
    }
    private function clients($bg): array {
        $n=[['Maria','Santos'],['Juan','DelaCruz'],['Ana','Reyes'],['Pedro','Garcia'],['Rosa','Lopez'],['Carlos','Martinez'],['Elena','Hernandez'],['Miguel','Gonzales'],['Sofia','Rodriguez'],['Antonio','Bautista'],['Isabel','Rivera'],['Jose','Cruz'],['Patricia','Torres'],['Manuel','Flores'],['Carmen','Ramos'],['Ricardo','Villanueva'],['Diana','Mendoza'],['Eduardo','Castro'],['Lucia','Diaz'],['Roberto','Aquino']];
        $r=[];
        foreach($n as $i=>[$f,$l]){
            $e=strtolower($f).'.'.strtolower($l).'@example.com';
            $u=User::withTrashed()->firstOrNew(['email'=>$e]);
            $u->forceFill(['first_name'=>$f,'last_name'=>$l,'email'=>$e,'password'=>self::$pw,'phone'=>'0917'.str_pad((string)($i+1000001),7,'0',STR_PAD_LEFT),'role'=>UserRole::Client,'status'=>UserStatus::Active,'email_verified_at'=>now(),'barangay_id'=>$bg->random()->id,'deleted_at'=>null])->save();
            $r[]=$u;
        }
        return $r;
    }
    private function providers($bg): array {
        $d=[['Mark','Plumbing',8],['Jenny','Electrical',6],['Rico','Aircon Services',10],['Liza','Carpentry',12],['Danny','House Cleaning',4],['Grace','Painting',7],['Roy','Appliance Repair',9],['Beth','General Home Maintenance',5],['Erwin','Plumbing',3],['Tina','Electrical',6],['Joel','Aircon Services',4],['Amy','House Cleaning',2]];
        $sk=['Plumbing'=>['Pipe Repair','Faucet Install','Leak Detection'],'Electrical'=>['Wiring','Outlets','Lighting'],'Aircon Services'=>['AC Cleaning','Freon Recharge'],'Carpentry'=>['Cabinet Making','Furniture Repair'],'House Cleaning'=>['Deep Cleaning','General Cleaning'],'Painting'=>['Interior','Exterior'],'Appliance Repair'=>['Washing Machine','Refrigerator'],'General Home Maintenance'=>['Minor Repairs','Fixture Install']];
        $r=[];
        foreach($d as $i=>[$f,$cat,$y]){
            $e='provider.'.strtolower($f).'@example.com';
            $u=User::withTrashed()->firstOrNew(['email'=>$e]);
            $b=$bg->random();
            $u->forceFill(['first_name'=>$f,'last_name'=>'Provider','email'=>$e,'password'=>self::$pw,'phone'=>'0918'.str_pad((string)($i+2000001),7,'0',STR_PAD_LEFT),'role'=>UserRole::Provider,'status'=>UserStatus::Active,'email_verified_at'=>now(),'barangay_id'=>$b->id,'deleted_at'=>null])->save();
            $v=$i<10;
            $p=ProviderProfile::firstOrNew(['user_id'=>$u->id]);
            $p->forceFill(['user_id'=>$u->id,'business_name'=>$f."'s ".$cat,'bio'=>"$cat provider $y yrs",'experience_years'=>$y,'base_barangay_id'=>$b->id,'latitude'=>$b->latitude,'longitude'=>$b->longitude,'verification_status'=>$v?VerificationStatus::Approved:VerificationStatus::Pending,'verified_at'=>$v?now()->subDays(rand(10,60)):null,'is_accepting_bookings'=>true])->save();
            foreach(($sk[$cat]??['General']) as $s){ProviderSkill::firstOrCreate(['provider_profile_id'=>$p->id,'skill'=>$s]);}
            for($dd=1;$dd<=6;$dd++){ProviderAvailabilityRule::firstOrCreate(['provider_profile_id'=>$p->id,'day_of_week'=>$dd],['start_time'=>'08:00','end_time'=>'17:00','slot_minutes'=>60,'is_active'=>true]);}
            foreach($bg->random(min(rand(3,5),$bg->count())) as $a){ProviderServiceArea::firstOrCreate(['provider_profile_id'=>$p->id,'barangay_id'=>$a->id]);}
            ProviderPaymentMethod::firstOrCreate(['provider_profile_id'=>$p->id,'type'=>PaymentMethodType::Gcash],['account_name'=>$u->full_name,'account_ref_masked'=>'**** '.substr($u->phone??'0000',-4),'is_default'=>true,'is_active'=>true]);
            $r[]=['user'=>$u,'profile'=>$p,'category'=>$cat];
        }
        return $r;
    }
    private function services(array $pr, $cats): array {
        $t=['Plumbing'=>[['Pipe Repair',500,60],['Faucet Install',800,90]],'Electrical'=>[['Outlet Install',600,45],['Lighting Install',800,60]],'Aircon Services'=>[['AC Cleaning Window',450,60],['AC Cleaning Split',600,90]],'Carpentry'=>[['Cabinet Repair',350,120]],'House Cleaning'=>[['General Cleaning',1200,180],['Deep Cleaning',2500,300]],'Painting'=>[['Room Painting',3000,480]],'Appliance Repair'=>[['Washer Repair',800,90],['Fridge Repair',1000,120]],'General Home Maintenance'=>[['Handyman HalfDay',1500,240]]];
        $sv=[];
        foreach($pr as $p){
            $c=$cats->firstWhere('name',$p['category']);
            if(!$c)continue;
            foreach(($t[$p['category']]??[]) as [$ti,$pr2,$du]){
                $s=Service::firstOrCreate(['provider_profile_id'=>$p['profile']->id,'title'=>$ti],['service_category_id'=>$c->id,'description'=>"Professional $ti service.",'pricing_type'=>PricingType::Fixed,'price'=>$pr2,'duration_minutes'=>$du,'is_active'=>true,'published_at'=>$p['profile']->isVerified()?now()->subDays(rand(1,30)):null]);
                $sv[]=$s;
            }
        }
        return $sv;
    }
    private function bookings(array $cl, array $pr, array $sv, $bg): array {
        $bk=[];
        $vp=collect($pr)->filter(fn($p)=>$p['profile']->isVerified());
        $ps=collect($sv)->filter(fn(Service $s)=>$s->isPublished());
        if($vp->isEmpty()||$ps->isEmpty())return[];
        $sets=[[BookingStatus::Completed,12,5,60],[BookingStatus::Accepted,4,0,3],[BookingStatus::Pending,4,-7,-1],[BookingStatus::InProgress,2,0,0],[BookingStatus::Cancelled,3,3,20],[BookingStatus::Rejected,2,5,15]];
        foreach($sets as [$st,$ct,$mn,$mx]){
            for($i=0;$i<$ct;$i++){
                $c=$cl[array_rand($cl)]; $pv=$vp->random();
                $ss=$ps->where('provider_profile_id',$pv['profile']->id);
                if($ss->isEmpty())continue;
                $s=$ss->random(); $da=rand($mn,$mx); $dt=now()->subDays($da); $h=rand(8,15);
                $b=new Booking;
                $b->forceFill(['booking_code'=>'WB-'.strtoupper(Str::random(6)),'client_id'=>$c->id,'provider_profile_id'=>$pv['profile']->id,'service_id'=>$s->id,'scheduled_date'=>$dt->format('Y-m-d'),'scheduled_start_time'=>sprintf('%02d:00',$h),'scheduled_end_time'=>sprintf('%02d:00',min($h+1,23)),'status'=>$st,'quoted_price'=>$s->price??rand(500,3000),'final_price'=>$st===BookingStatus::Completed?($s->price??rand(500,3000)):null,'accepted_at'=>in_array($st,[BookingStatus::Accepted,BookingStatus::InProgress,BookingStatus::Completed])?$dt->copy()->subHours(rand(1,12)):null,'started_at'=>in_array($st,[BookingStatus::InProgress,BookingStatus::Completed])?$dt->copy():null,'completed_at'=>$st===BookingStatus::Completed?$dt->copy()->addHours(rand(1,3)):null,'cancelled_at'=>$st===BookingStatus::Cancelled?$dt:null,'cancelled_by'=>$st===BookingStatus::Cancelled?$c->id:null])->save();
                $lb=$bg->random();
                BookingLocation::firstOrCreate(['booking_id'=>$b->id],['barangay_id'=>$lb->id,'address_line'=>rand(1,200).' Rizal St.','latitude'=>$lb->latitude??14.34,'longitude'=>$lb->longitude??120.85]);
                BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>null,'to_status'=>BookingStatus::Pending,'changed_by'=>$c->id]);
                if(in_array($st,[BookingStatus::Accepted,BookingStatus::InProgress,BookingStatus::Completed]))BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>BookingStatus::Pending,'to_status'=>BookingStatus::Accepted,'changed_by'=>$pv['user']->id]);
                if(in_array($st,[BookingStatus::InProgress,BookingStatus::Completed]))BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>BookingStatus::Accepted,'to_status'=>BookingStatus::InProgress,'changed_by'=>$pv['user']->id]);
                if($st===BookingStatus::Completed)BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>BookingStatus::InProgress,'to_status'=>BookingStatus::Completed,'changed_by'=>$pv['user']->id]);
                if($st===BookingStatus::Rejected)BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>BookingStatus::Pending,'to_status'=>BookingStatus::Rejected,'changed_by'=>$pv['user']->id,'reason'=>'Fully booked.']);
                if($st===BookingStatus::Cancelled)BookingStatusHistory::create(['booking_id'=>$b->id,'from_status'=>BookingStatus::Pending,'to_status'=>BookingStatus::Cancelled,'changed_by'=>$c->id,'reason'=>'Conflict']);
                $bk[]=$b;
            }
        }
        foreach($vp as $p){$cnt=Booking::where('provider_profile_id',$p['profile']->id)->where('status',BookingStatus::Completed)->count();$p['profile']->forceFill(['completed_bookings_count'=>$cnt])->save();}
        return $bk;
    }
    private function reviews(array $bk): void {
        $done=collect($bk)->filter(fn(Booking $b)=>$b->status===BookingStatus::Completed);
        foreach($done as $b){if(rand(1,10)>8)continue;Review::firstOrCreate(['booking_id'=>$b->id],['client_id'=>$b->client_id,'provider_profile_id'=>$b->provider_profile_id,'rating'=>rand(3,5),'comment'=>'Great service!','is_visible'=>true]);}
        $pids=Review::pluck('provider_profile_id')->unique();
        foreach($pids as $pid){$avg=Review::where('provider_profile_id',$pid)->where('is_visible',true)->avg('rating');$cnt=Review::where('provider_profile_id',$pid)->where('is_visible',true)->count();ProviderProfile::where('id',$pid)->update(['rating_avg'=>round($avg,2),'rating_count'=>$cnt]);}
    }
    private function payments(array $bk): void {
        $pay=collect($bk)->filter(fn(Booking $b)=>in_array($b->status,[BookingStatus::Accepted,BookingStatus::InProgress,BookingStatus::Completed]));
        foreach($pay as $b){$pm=ProviderPaymentMethod::where('provider_profile_id',$b->provider_profile_id)->first();$st=$b->status===BookingStatus::Completed?PaymentStatus::Verified:PaymentStatus::Pending;Payment::firstOrCreate(['booking_id'=>$b->id],['client_id'=>$b->client_id,'provider_profile_id'=>$b->provider_profile_id,'provider_payment_method_id'=>$pm?->id,'amount'=>$b->final_price??$b->quoted_price??1000,'status'=>$st,'reference_number'=>$st===PaymentStatus::Verified?strtoupper(Str::random(8)):null,'verified_at'=>$st===PaymentStatus::Verified?now():null]);}
    }
    private function convos(array $bk, array $pr): void {
        $cb=collect($bk)->filter(fn($b)=>in_array($b->status,[BookingStatus::Accepted,BookingStatus::InProgress,BookingStatus::Completed]))->take(10);
        foreach($cb as $b){$pv=collect($pr)->first(fn($p)=>$p['profile']->id===$b->provider_profile_id);if(!$pv)continue;$c=Conversation::firstOrCreate(['client_id'=>$b->client_id,'provider_user_id'=>$pv['user']->id],['booking_id'=>$b->id,'last_message_at'=>now()]);if($c->messages()->count()===0){Message::create(['conversation_id'=>$c->id,'sender_id'=>$b->client_id,'body'=>'Hi, booked your service.','moderation_status'=>ModerationStatus::Allowed]);Message::create(['conversation_id'=>$c->id,'sender_id'=>$pv['user']->id,'body'=>'Hello! See you then.','moderation_status'=>ModerationStatus::Allowed]);}}
    }
    private function settings(): void {
        SystemSetting::firstOrCreate(['key'=>'platform.name'],['value'=>'WEBIS','group'=>'general','description'=>'Platform name']);
        SystemSetting::firstOrCreate(['key'=>'booking.min_lead_hours'],['value'=>24,'group'=>'booking','description'=>'Min lead hours']);
        SystemSetting::firstOrCreate(['key'=>'booking.max_advance_days'],['value'=>30,'group'=>'booking','description'=>'Max advance days']);
    }
}
