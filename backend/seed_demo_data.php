<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/DbSchema.php';
require_once __DIR__ . '/utils/ActivityLogger.php';

header('Content-Type: application/json; charset=UTF-8');

function j($x, $c = 200) { http_response_code($c); echo json_encode($x, JSON_PRETTY_PRINT); exit; }
function dt($s) { return (new DateTimeImmutable($s))->format('Y-m-d H:i:s'); }
function tExists(PDO $db, $t) { $s=$db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?"); $s->execute([$t]); return (int)$s->fetchColumn()>0; }
function cols(PDO $db, $t) { if(!tExists($db,$t)) return []; $r=$db->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC)?:[]; $o=[]; foreach($r as $c){$o[$c['Field']]=1;} return $o; }
function hasCol(PDO $db, $t, $c) { $k=cols($db,$t); return isset($k[$c]); }
function addCol(PDO $db, $t, $c, $def) { if (tExists($db,$t) && !hasCol($db,$t,$c)) $db->exec("ALTER TABLE `{$t}` ADD COLUMN {$def}"); }
function ins(PDO $db, $t, array $d) {
    if (!tExists($db,$t)) return 0;
    $k = cols($db,$t); $f=[]; $v=[];
    foreach($d as $c=>$x){ if(isset($k[$c])){ $f[]=$c; $v[]=$x; } }
    if (!$f) return 0;
    $q = "INSERT INTO `{$t}` (`".implode('`,`',$f)."`) VALUES (".implode(',',array_fill(0,count($f),'?')).")";
    $s = $db->prepare($q); $s->execute($v); return (int)$db->lastInsertId();
}

try {
    $db = (new Database())->getConnection();
    if (!$db) j(['ok'=>false,'message'=>'DB connection failed'],500);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    DbSchema::ensureStaffShifts($db);
    DbSchema::ensureAppointments($db);
    DbSchema::ensureReceptionHandover($db);
    DbSchema::ensureVisitEncounters($db);
    DbSchema::ensureReceptionIdentityTables($db);
    DbSchema::ensureReceptionPatientMedicalAidColumns($db);
    DbSchema::ensureNurseModules($db);
    DbSchema::ensureDoctorModules($db);
    DbSchema::ensureReferralRegistry($db);
    DbSchema::ensurePharmacyModules($db);
    DbSchema::ensureITModules($db);
    DbSchema::ensureUserRole($db, 'nurse_in_charge');
    DbSchema::ensureUserRole($db, 'nurse_aid');
    DbSchema::ensureUserRole($db, 'senior_pharmacist');
    DbSchema::ensureUserRole($db, 'it_support');
    DbSchema::ensureNurseInChargeRole($db, true);
    ActivityLogger::ensureTables($db);

    $db->exec("CREATE TABLE IF NOT EXISTS patient_queue (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, doctor_assigned INT NULL, status VARCHAR(50) NOT NULL DEFAULT 'Waiting', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS patient_vitals (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, queue_id INT NULL, temperature VARCHAR(10), pulse VARCHAR(10), bp VARCHAR(20), weight VARCHAR(10), spo2 VARCHAR(10), notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS beds (id INT AUTO_INCREMENT PRIMARY KEY, ward_name VARCHAR(100) NOT NULL, bed_number VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'Available', current_patient_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS medical_reports (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, report_type VARCHAR(50) DEFAULT 'Document', report_name VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    addCol($db,'patients','full_name',"full_name VARCHAR(150) NULL");
    addCol($db,'patients','national_id',"national_id VARCHAR(50) NULL");
    addCol($db,'patients','phone',"phone VARCHAR(25) NULL");
    addCol($db,'patients','address',"address VARCHAR(255) NULL");
    addCol($db,'patients','has_medical_aid',"has_medical_aid TINYINT(1) NOT NULL DEFAULT 0");
    addCol($db,'patients','medical_aid_provider',"medical_aid_provider VARCHAR(120) NULL");
    addCol($db,'patients','medical_aid_number',"medical_aid_number VARCHAR(60) NULL");
    addCol($db,'patients','medical_aid_member_name',"medical_aid_member_name VARCHAR(150) NULL");
    addCol($db,'patients','medical_aid_suffix',"medical_aid_suffix VARCHAR(30) NULL");
    addCol($db,'patients','medical_aid_plan',"medical_aid_plan VARCHAR(120) NULL");
    addCol($db,'patients','medical_aid_date_joined',"medical_aid_date_joined DATE NULL");
    addCol($db,'patients','kin_name',"kin_name VARCHAR(150) NULL");
    addCol($db,'patients','kin_relation',"kin_relation VARCHAR(80) NULL");
    addCol($db,'patients','kin_phone',"kin_phone VARCHAR(25) NULL");
    addCol($db,'patients','allergies',"allergies TEXT NULL");
    if (hasCol($db,'patients','name') && hasCol($db,'patients','full_name')) $db->exec("UPDATE patients SET full_name=name WHERE (full_name IS NULL OR full_name='') AND name IS NOT NULL");

    addCol($db,'medicines','batch_number',"batch_number VARCHAR(60) NULL");
    addCol($db,'medicines','unit',"unit VARCHAR(30) NULL");
    addCol($db,'prescriptions','patient_id',"patient_id INT NULL");
    addCol($db,'prescriptions','medicine_id',"medicine_id INT NULL");
    addCol($db,'prescriptions','quantity',"quantity INT NOT NULL DEFAULT 1");
    addCol($db,'prescriptions','notes',"notes TEXT NULL");


    $pw = password_hash('Demo123!', PASSWORD_BCRYPT);
    $users = [
        ['demo_doc1','demo.doc1@hms.local','Nurse In Charge Tariro Ncube','nurse_in_charge'],
        ['demo_doc2','demo.doc2@hms.local','Nurse In Charge Peter Moyo','nurse_in_charge'],
        ['demo_nurse1','demo.nurse1@hms.local','Nurse Betty Chari','nurse'],
        ['demo_na1','demo.na1@hms.local','Nurse Aid Kelvin Zulu','nurse_aid'],
        ['demo_rx1','demo.rx1@hms.local','Pharm. Lee Banda','pharmacist'],
        ['demo_rxs','demo.rxs@hms.local','Senior Pharm. Anita Dube','senior_pharmacist'],
        ['demo_rec1','demo.rec1@hms.local','Reception John Mbewe','receptionist'],
        ['demo_it1','demo.it1@hms.local','IT Ryan Banda','it_support']
    ];
    $uid = [];
    foreach ($users as $u) {
        $q=$db->prepare("SELECT id FROM users WHERE username=? LIMIT 1"); $q->execute([$u[0]]); $id=(int)$q->fetchColumn();
        if ($id>0) {
            $db->prepare("UPDATE users SET email=?,password_hash=?,full_name=?,role=?,is_active=1 WHERE id=?")->execute([$u[1],$pw,$u[2],$u[3],$id]);
        } else {
            $id = ins($db,'users',['username'=>$u[0],'email'=>$u[1],'password_hash'=>$pw,'full_name'=>$u[2],'role'=>$u[3],'is_active'=>1]);
        }
        $uid[$u[0]]=$id;
    }

    $old = $db->query("SELECT id FROM patients WHERE national_id LIKE 'DEMO-%'")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if ($old) {
        $in = implode(',', array_fill(0,count($old),'?'));
        foreach (['patient_vitals','patient_queue','prescriptions','appointments','pharmacy_requests','refill_requests','nurse_tasks','doctor_tasks','nurse_escalations','doctor_escalations','discharge_summaries','medical_reports','insurance_claims'] as $t) {
            if (tExists($db,$t) && hasCol($db,$t,'patient_id')) { $s=$db->prepare("DELETE FROM {$t} WHERE patient_id IN ({$in})"); $s->execute($old); }
        }
        if (tExists($db,'beds') && hasCol($db,'beds','current_patient_id')) { $s=$db->prepare("UPDATE beds SET current_patient_id=NULL,status='Available' WHERE current_patient_id IN ({$in})"); $s->execute($old); }
        $s=$db->prepare("DELETE FROM patients WHERE id IN ({$in})"); $s->execute($old);
    }
    if (tExists($db,'it_tickets')) $db->exec("DELETE FROM it_tickets WHERE title LIKE '[DEMO]%'");
    if (tExists($db,'reception_handover')) $db->exec("DELETE FROM reception_handover WHERE notes LIKE '[DEMO]%'");
    if (tExists($db,'nurse_handover')) $db->exec("DELETE FROM nurse_handover WHERE notes LIKE '[DEMO]%'");
    if (tExists($db,'doctor_handover')) $db->exec("DELETE FROM doctor_handover WHERE notes LIKE '[DEMO]%'");

    $names = [
        ['Musa Ndlovu','male'],['Rudo Chuma','female'],['Tapiwa Banda','male'],['Linda Moyo','female'],['Brian Dube','male'],
        ['Nomsa Zulu','female'],['Kelvin Mbewe','male'],['Alice Phiri','female'],['Tendai Tembo','male'],['Naomi Mumba','female'],
        ['Precious Nyoni','female'],['Lerato Kapwepwe','female'],['Paul Chari','male'],['Clara Zhou','female'],['Sharon Maseko','female'],
        ['Victor Maphosa','male'],['Tatenda Sibanda','male'],['Mildred Nkomo','female'],['Elvis Chitambo','male'],['Tamara Daka','female']
    ];
    $pids = [];
    foreach ($names as $i=>$n) {
        $pids[] = ins($db,'patients',[
            'full_name'=>$n[0],'national_id'=>sprintf('DEMO-%04d',$i+1),'dob'=>(new DateTimeImmutable('-'.(21+($i%45)).' years'))->format('Y-m-d'),
            'gender'=>$n[1],'phone'=>'555-01'.str_pad((string)$i,2,'0',STR_PAD_LEFT),'address'=>'Demo Address '.($i+1),
            'has_medical_aid'=>($i%3===0)?1:0,'medical_aid_provider'=>($i%3===0)?'MediAid':null,'medical_aid_number'=>($i%3===0)?'MA-'.(1200+$i):null,
            'medical_aid_member_name'=>($i%3===0)?$n[0]:null,'medical_aid_plan'=>($i%3===0)?'Standard Medical Aid Cover':null,
            'kin_name'=>'Kin '.$n[0],'kin_relation'=>'Relative','kin_phone'=>'555-77'.str_pad((string)$i,2,'0',STR_PAD_LEFT),'allergies'=>($i%5===0)?'Penicillin':null,'created_at'=>dt('-'.($i%6).' days')
        ]);
    }

    $meds = [
        ['Paracetamol','General',2.5,450,'+420 days','B-1102','boxes'],['Amoxicillin','Antibiotics',8,210,'+250 days','B-2291','boxes'],
        ['Losartan','Cardiac',14,120,'+360 days','B-3301','boxes'],['Atorvastatin','Cardiac',18,95,'+300 days','B-3402','boxes'],
        ['Metformin','Endocrine',7,180,'+390 days','B-4501','boxes'],['Insulin','Endocrine',45,64,'+180 days','B-4511','vials'],
        ['Ceftriaxone','Antibiotics',20,88,'+210 days','B-5503','vials'],['Salbutamol','Respiratory',12,130,'+300 days','B-6602','inhalers'],
        ['Morphine','Controlled',55,34,'+220 days','B-9901','amps'],['Diazepam','Controlled',16,74,'+330 days','B-8802','boxes']
    ];
    $mids=[];
    foreach($meds as $m){ $s=$db->prepare("SELECT id FROM medicines WHERE name=? LIMIT 1"); $s->execute([$m[0]]); $id=(int)$s->fetchColumn();
        if($id<=0)$id=ins($db,'medicines',['name'=>$m[0],'category'=>$m[1],'price'=>$m[2],'stock_quantity'=>$m[3],'expiry_date'=>(new DateTimeImmutable($m[4]))->format('Y-m-d'),'batch_number'=>$m[5],'unit'=>$m[6]]);
        if($id>0)$mids[]=$id;
    }

    if (tExists($db,'beds') && (int)$db->query("SELECT COUNT(*) FROM beds")->fetchColumn() < 12) {
        foreach(['Ward A','Ward B','Ward C'] as $w){ for($i=1;$i<=6;$i++) ins($db,'beds',['ward_name'=>$w,'bed_number'=>substr($w,-1).'-'.str_pad((string)$i,2,'0',STR_PAD_LEFT),'status'=>'Available']); }
    }

    $qs = ['Waiting','Waiting','In Triage','With Doctor','With Doctor','Urgent Care','Admission Pending','Waiting Pharmacy','Ready for Admission','Completed','Cancelled','Discharged'];
    $qids=[];
    foreach($pids as $i=>$pid){ $qids[$pid]=ins($db,'patient_queue',['patient_id'=>$pid,'doctor_assigned'=>($i%2===0?$uid['demo_doc1']:$uid['demo_doc2']),'status'=>$qs[$i%count($qs)],'created_at'=>dt('-'.($i%7).' days +'.(8+($i%8)).' hours')]); }

    $visitIds = [];
    if (tExists($db, 'visits')) {
        foreach ($pids as $i => $pid) {
            $visitIds[$pid] = ins($db, 'visits', [
                'patient_id' => $pid,
                'doctor_id' => ($i % 2 === 0 ? $uid['demo_doc1'] : $uid['demo_doc2']),
                'status' => 'waiting',
                'chief_complaint' => 'General consultation',
                'created_at' => dt('-' . ($i % 7) . ' days +'.(8+($i%8)).' hours'),
                'updated_at' => dt('now')
            ]);
        }
    }

    foreach($pids as $i=>$pid){ ins($db,'patient_vitals',['patient_id'=>$pid,'queue_id'=>$qids[$pid]??null,'temperature'=>(string)(36.4+(($i%6)*0.2)),'pulse'=>(string)(72+($i%22)),'bp'=>(118+($i%24)).'/'.(74+($i%12)),'weight'=>(string)(58+($i%18)),'spo2'=>(string)(93+($i%6)),'notes'=>'[DEMO] triage','created_at'=>dt('-'.($i%7).' days +'.(9+($i%8)).' hours')]); }

    $rxids=[];
    foreach($pids as $i=>$pid){ $st=($i%5===0)?'Dispensed':(($i%4===0)?'External':'Pending'); $mid=($st==='External'||!$mids)?null:$mids[$i%count($mids)];
        $rxids[] = ins($db,'prescriptions',['visit_id'=>($visitIds[$pid] ?? null),'patient_id'=>$pid,'medicine_id'=>$mid,'quantity'=>1+($i%3),'dosage'=>($i%2===0?'1 tab bd':'1 tab od'),'notes'=>($st==='External'?'External medication':'[DEMO] medication'),'status'=>$st,'created_at'=>dt('-'.($i%7).' days +'.(10+($i%6)).' hours')]);
    }

    foreach($pids as $i=>$pid){ ins($db,'appointments',['patient_id'=>$pid,'doctor_id'=>($i%2===0?$uid['demo_doc1']:$uid['demo_doc2']),'scheduled_at'=>dt(($i%2===0?'+':'-').($i%4).' days +'.(8+($i%7)).' hours'),'status'=>['scheduled','confirmed','checked_in','completed','cancelled'][$i%5],'reason'=>'Follow-up','notes'=>'[DEMO] appt','created_at'=>dt('-'.($i%6).' days')]); }

    if (tExists($db,'staff_shifts')) {
        $db->prepare("DELETE FROM staff_shifts WHERE user_id IN (?,?,?,?,?,?,?,?)")->execute(array_values($uid));
        $sr=[[$uid['demo_doc1'],'nurse_in_charge','Day','Ward A'],[$uid['demo_doc2'],'nurse_in_charge','Evening','Ward B'],[$uid['demo_nurse1'],'nurse','Day','Ward A'],[$uid['demo_na1'],'nurse_aid','Day','Ward B'],[$uid['demo_rx1'],'pharmacist','Day','Pharmacy'],[$uid['demo_rec1'],'receptionist','Day','Front Desk']];
        foreach($sr as $r){ ins($db,'staff_shifts',['user_id'=>$r[0],'role'=>$r[1],'shift_start'=>dt('today 07:00'),'shift_end'=>dt('today 15:00'),'shift_type'=>$r[2],'ward_name'=>$r[3],'status'=>'Confirmed']); ins($db,'staff_shifts',['user_id'=>$r[0],'role'=>$r[1],'shift_start'=>dt('+1 days 07:00'),'shift_end'=>dt('+1 days 15:00'),'shift_type'=>$r[2],'ward_name'=>$r[3],'status'=>'Confirmed']); }
    }

    for($i=0;$i<6;$i++){ ins($db,'nurse_tasks',['patient_id'=>$pids[$i],'assigned_to'=>$uid['demo_nurse1'],'task'=>'[DEMO] Monitor vitals','priority'=>['high','normal','low'][$i%3],'status'=>['open','in_progress','done'][$i%3],'due_at'=>dt('+'.($i+1).' hours'),'created_at'=>dt('-'.($i%3).' days')]); }
    for($i=0;$i<5;$i++){ ins($db,'doctor_tasks',['patient_id'=>$pids[$i+3],'assigned_to'=>$uid['demo_doc1'],'task'=>'[DEMO] Review labs','priority'=>['high','normal','low'][$i%3],'status'=>['open','in_progress','done'][$i%3],'due_at'=>dt('+'.($i+2).' hours'),'created_at'=>dt('-'.($i%4).' days')]); }
    for($i=0;$i<4;$i++){ ins($db,'nurse_escalations',['patient_id'=>$pids[$i+5],'reason'=>'[DEMO] Abnormal trend','severity'=>['urgent','high','medium'][$i%3],'status'=>['open','triaged','closed'][$i%3],'created_at'=>dt('-'.($i%5).' days')]); }
    for($i=0;$i<4;$i++){ ins($db,'doctor_escalations',['patient_id'=>$pids[$i+7],'reason'=>'[DEMO] Specialist referral','severity'=>['urgent','high','medium'][$i%3],'status'=>['open','triaged','closed'][$i%3],'created_at'=>dt('-'.($i%5).' days')]); }
    for($i=0;$i<4;$i++){ ins($db,'discharge_summaries',['patient_id'=>$pids[$i+10],'summary'=>'[DEMO] Discharge summary','status'=>['pending','approved','completed'][$i%3],'created_at'=>dt('-'.($i%3).' days')]); }

    ins($db,'reception_handover',['user_id'=>$uid['demo_rec1'],'shift_start'=>dt('-1 days 07:00'),'shift_end'=>dt('-1 days 15:00'),'notes'=>'[DEMO] Morning queue surge']);
    ins($db,'nurse_handover',['user_id'=>$uid['demo_nurse1'],'shift_start'=>dt('-1 days 07:00'),'shift_end'=>dt('-1 days 19:00'),'notes'=>'[DEMO] Monitor ward B oxygen cases']);
    ins($db,'doctor_handover',['user_id'=>$uid['demo_doc1'],'shift_start'=>dt('-1 days 07:00'),'shift_end'=>dt('-1 days 15:00'),'notes'=>'[DEMO] Follow-up cardiology']);

    for($i=0;$i<6;$i++){ ins($db,'pharmacy_requests',['patient_id'=>$pids[$i],'requested_by'=>$uid['demo_nurse1'],'status'=>['Pending','Ready','Completed'][$i%3],'notes'=>'[DEMO] Pharmacy prep','created_at'=>dt('-'.($i%4).' days')]); }
    for($i=0;$i<5;$i++){ ins($db,'refill_requests',['patient_id'=>$pids[$i+2],'medicine_name'=>'Maintenance Med '.($i+1),'quantity'=>1+($i%2),'notes'=>'[DEMO] refill','requested_by'=>$uid['demo_rec1'],'status'=>['Requested','Approved','Dispensed'][$i%3],'created_at'=>dt('-'.($i%5).' days')]); }
    ins($db,'drug_interactions',['drug_a'=>'Warfarin','drug_b'=>'Aspirin','severity'=>'major','notes'=>'[DEMO] Monitor bleeding risk']);
    if(!empty($mids)){
        $cm = $mids[count($mids)-1];
        $chk = $db->prepare("SELECT id FROM controlled_substances WHERE medicine_id = ? LIMIT 1");
        $chk->execute([$cm]);
        if (!$chk->fetchColumn()) {
            ins($db,'controlled_substances',['medicine_id'=>$cm,'schedule'=>'S4','requires_approval'=>1,'notes'=>'[DEMO] controlled']);
        }
    }
    if(!empty($rxids)){ ins($db,'controlled_requests',['prescription_id'=>$rxids[0],'requested_by'=>$uid['demo_rx1'],'approved_by'=>$uid['demo_rxs'],'status'=>'Approved','notes'=>'[DEMO] controlled req','approved_at'=>dt('-1 days'),'created_at'=>dt('-2 days')]); }
    $sidStmt = $db->prepare("SELECT id FROM suppliers WHERE name = ? LIMIT 1");
    $sidStmt->execute(['MedSupply Ltd']);
    $sid = (int)$sidStmt->fetchColumn();
    if ($sid <= 0) {
        $sid = ins($db,'suppliers',['name'=>'MedSupply Ltd','contact_name'=>'Procurement','phone'=>'555-6600','email'=>'medsupply@demo.local','address'=>'Demo industrial']);
    }
    $po=ins($db,'purchase_orders',['supplier_id'=>$sid,'order_date'=>(new DateTimeImmutable('today'))->format('Y-m-d'),'status'=>'Pending','total_amount'=>450,'created_by'=>$uid['demo_rxs'],'created_at'=>dt('-1 days')]);
    if($po){ ins($db,'purchase_order_items',['order_id'=>$po,'medicine_name'=>'Paracetamol','quantity'=>20,'unit_cost'=>5,'total_cost'=>100]); ins($db,'goods_receipts',['order_id'=>$po,'received_by'=>$uid['demo_rxs'],'received_date'=>(new DateTimeImmutable('today'))->format('Y-m-d'),'notes'=>'[DEMO] goods']); ins($db,'purchase_invoices',['order_id'=>$po,'invoice_number'=>'DEMO-INV-'.$po,'amount'=>450,'invoice_date'=>(new DateTimeImmutable('today'))->format('Y-m-d'),'status'=>'Open']); }
    if(!empty($mids)){ ins($db,'quarantine_batches',['medicine_id'=>$mids[0],'batch_number'=>'Q-DEMO-01','quantity'=>5,'reason'=>'Damaged pack','status'=>'Quarantined','created_at'=>dt('-1 days')]); ins($db,'stock_adjustments',['medicine_id'=>$mids[1],'adjustment'=>-2,'reason'=>'Breakage','adjusted_by'=>$uid['demo_rxs'],'created_at'=>dt('-1 days')]); }
    if(!empty($rxids)){ ins($db,'insurance_claims',['prescription_id'=>$rxids[1],'patient_id'=>$pids[1],'status'=>'Submitted','submitted_by'=>$uid['demo_rx1'],'submitted_at'=>dt('-1 days'),'notes'=>'[DEMO] claim']); }

    ins($db,'it_tickets',['title'=>'[DEMO] Printer offline in OPD','description'=>'Queue stuck on nurse station printer','status'=>'open','priority'=>'medium','source'=>'dashboard','requester_name'=>'Demo Staff','requester_email'=>'demo.staff@hms.local','created_by'=>$uid['demo_it1'],'assigned_to'=>$uid['demo_it1'],'created_at'=>dt('-1 days'),'updated_at'=>dt('now')]);
    ins($db,'it_tickets',['title'=>'[DEMO] Queue dashboard timeout','description'=>'Intermittent timeout under load','status'=>'in_progress','priority'=>'high','source'=>'dashboard','requester_name'=>'Demo Staff','requester_email'=>'demo.staff@hms.local','created_by'=>$uid['demo_it1'],'assigned_to'=>$uid['demo_it1'],'created_at'=>dt('-2 days'),'updated_at'=>dt('now')]);

    foreach([
        ['demo_rec1','Checked-in appointment','Success','reception','appointments','web','INFO',200,null],
        ['demo_nurse1','Escalated patient vitals','Warn','nurse','patient_vitals','nurse','WARN',429,'[DEMO] warning signal'],
        ['demo_doc1','Completed consultation','Success','nurse_in_charge','patient_queue','doctor','INFO',200,null],
        ['demo_rx1','Dispensed medication','Success','pharmacy','prescriptions','pharmacy','INFO',200,null],
        ['demo_it1','Ticket sync failure','Error','it_support','it_tickets','it','ERROR',500,'[DEMO] expected error sample']
    ] as $l){
        ActivityLogger::log($db,$l[0],$l[1],$l[2],null,['event_type'=>'audit','actor_id'=>(string)$uid[$l[0]],'actor_role'=>$l[3],'entity_type'=>$l[4],'source'=>$l[5],'severity'=>$l[6],'status_code'=>$l[7],'error_message'=>$l[8]]);
    }

    if (tExists($db,'beds')) {
        $b = $db->query("SELECT id FROM beds WHERE status='Available' ORDER BY id ASC LIMIT 6")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach($b as $i=>$bid){ $db->prepare("UPDATE beds SET status='Occupied', current_patient_id=? WHERE id=?")->execute([$pids[$i],$bid]); }
    }

    j([
        'ok'=>true,
        'message'=>'Real demo data seeded. Demo staff password: Demo123!',
        'summary'=>[
            'patients'=>(int)$db->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
            'queue'=>(int)$db->query("SELECT COUNT(*) FROM patient_queue")->fetchColumn(),
            'vitals'=>(int)$db->query("SELECT COUNT(*) FROM patient_vitals")->fetchColumn(),
            'prescriptions'=>(int)$db->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn(),
            'appointments'=>tExists($db,'appointments')?(int)$db->query("SELECT COUNT(*) FROM appointments")->fetchColumn():0,
            'it_tickets'=>tExists($db,'it_tickets')?(int)$db->query("SELECT COUNT(*) FROM it_tickets")->fetchColumn():0
        ]
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
    j(['ok'=>false,'message'=>'Seeding failed','error'=>$e->getMessage()],500);
}
