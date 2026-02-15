<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

/* doar admin */
$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || (($_SESSION['rol'] ?? '') === 'admin');
if (!$isAdmin) { $_SESSION['errorMessage'] = 'Acces interzis.'; header('Location: manageReservations.php'); exit; }

/* DB */
try {
  $db  = new DatabaseConnector();
  $pdo = $db->connect();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
  $_SESSION['errorMessage'] = 'Eroare conexiune DB: '.$e->getMessage();
  header('Location: manageReservations.php'); exit;
}

/* upload */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $_SESSION['errorMessage']='Metodă invalidă.'; header('Location: manageReservations.php'); exit; }
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['errorMessage'] = 'Eroare upload (selectează un .csv valid).';
  header('Location: manageReservations.php'); exit;
}
$path = $_FILES['file']['tmp_name'];
if (!is_readable($path)) { $_SESSION['errorMessage']='Nu pot citi fișierul.'; header('Location: manageReservations.php'); exit; }

/* utils */
function detect_delim(string $file): string {
  $fh=fopen($file,'r'); if(!$fh) return ',';
  $chunk=fgets($fh,4096); fclose($fh);
  $c=[","=>0,";"=>0,"\t"=>0,"|"=>0]; foreach($c as $d=>$_){ $c[$d]=substr_count((string)$chunk,$d); }
  arsort($c); $k=array_key_first($c); return $c[$k]>0?$k:',';
}
function nlabel(string $s): string {
  $s=preg_replace('/^\xEF\xBB\xBF/','',$s);                 // BOM
  $s=str_replace(["\xC2\xA0","\xE2\x80\x8B","\xE2\x80\x8C","\xE2\x80\x8D"],' ',$s); // invizibile
  $s=trim($s);
  $t=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if($t!==false) $s=$t;
  $s=strtolower($s);
  $s=preg_replace('/[^a-z0-9]+/','_',$s);
  return trim($s,'_');
}
function cell($v): string { return trim((string)$v); }
function to_int($v): ?int { $v=cell($v); if($v===''||!preg_match('/^\d+$/',$v)) return null; return (int)$v; }
function to_float($v): ?float {
  $s=trim(str_replace([' ', ','], ['', '.'], (string)$v));
  if($s===''||!is_numeric($s)) return null; return (float)$s;
}
function map_plata(string $s): ?string {
  $x=strtolower(trim($s));
  if($x==='') return null;
  if(str_contains($x,'paypal')||str_contains($x,'card')) return 'paypal';
  if(str_contains($x,'cash')||str_contains($x,'numerar')||str_contains($x,'sediu')) return 'plata_sediu';
  return null; // enum permite NULL
}

$delim = detect_delim($path);
$fh = fopen($path,'r'); if($fh===false){ $_SESSION['errorMessage']='Nu pot deschide CSV.'; header('Location: manageReservations.php'); exit; }

/* header */
$raw = fgetcsv($fh,0,$delim);
if($raw===false){ fclose($fh); $_SESSION['errorMessage']='CSV gol.'; header('Location: manageReservations.php'); exit; }
$norm=[]; $mapIdx=[];
foreach($raw as $i=>$h){ $n=nlabel((string)$h); $norm[$i]=$n; if($n!=='') $mapIdx[$n]=$i; }

/* aliasuri */
$aliases = [
  'id_client'      => ['id_client','client_id','id_user','id_utilizator','idclient'],
  'id_pachet'      => ['id_pachet','pachet_id','package_id','idpachet'],
  'email'          => ['email','e_mail'],
  'nume'           => ['nume','last_name','lastname','surname'],
  'prenume'        => ['prenume','first_name','firstname','given_name'],
  'telefon'        => ['telefon','phone','tel','mobile'],
  'numar_persoane' => ['numar_persoane','nr_persoane','people'],
  'adresa'         => ['adresa','address','addr'],
  'judet'          => ['judet','county','region'],
  'localitate'     => ['localitate','oras','city','town'],
  'metoda_plata'   => ['metoda_plata','payment_method','plata'],
  'data_rezervare' => ['data_rezervare','data','date'],
  'total'          => ['total','suma','valoare']
];
$idx=[];
foreach($aliases as $k=>$alts){
  foreach($alts as $a){ $a=nlabel($a); if(isset($mapIdx[$a])){ $idx[$k]=$mapIdx[$a]; break; } }
}

/* minime necesare: id_client, id_pachet */
if(!isset($idx['id_client']) || !isset($idx['id_pachet'])){
  fclose($fh);
  $_SESSION['errorMessage']="CSV invalid: coloanele 'id_client' și 'id_pachet' sunt obligatorii. (delim: ".($delim=="\t"?"TAB":$delim).")";
  header('Location: manageReservations.php'); exit;
}

/* pregătite */
$checkUser = $pdo->prepare("SELECT 1 FROM useri WHERE id=:id LIMIT 1");
$checkPack = $pdo->prepare("SELECT pret FROM pacheteturistice WHERE id_pachet=:id LIMIT 1");
$existsRes = $pdo->prepare("SELECT 1 FROM rezervari WHERE id_client=:c AND id_pachet=:p LIMIT 1");

$insRes = $pdo->prepare("
  INSERT INTO rezervari
   (id_client,id_pachet,data_rezervare,numar_persoane,total,adresa_fizica,judet,localitate,metoda_plata,mentiuni_speciale,tip_persoana,email,nume,prenume,telefon,adresa)
  VALUES
   (:c,:p,:data,:npers,:total,:adresa_fizica,:judet,:localitate,:metoda,:mentiuni,:tip,:email,:nume,:prenume,:telefon,:adresa)
");
$updRes = $pdo->prepare("
  UPDATE rezervari SET
    data_rezervare=:data, numar_persoane=:npers, total=:total,
    adresa_fizica=:adresa_fizica, judet=:judet, localitate=:localitate, metoda_plata=:metoda,
    email=:email, nume=:nume, prenume=:prenume, telefon=:telefon, adresa=:adresa
  WHERE id_client=:c AND id_pachet=:p
");

/* import */
$line=1; $okI=0;$okU=0;$skip=0;$err=0; $errs=[];

$pdo->beginTransaction();
while(($row=fgetcsv($fh,0,$delim))!==false){
  $line++;

  $idc = to_int($row[$idx['id_client']] ?? null);
  $idp = to_int($row[$idx['id_pachet']] ?? null);
  if(!$idc || !$idp){ $skip++; $errs[]="#$line id_client/id_pachet invalid"; continue; }

  // FK existente?
  $checkUser->execute([':id'=>$idc]); if(!$checkUser->fetchColumn()){ $skip++; $errs[]="#$line user inexistent ($idc)"; continue; }
  $checkPack->execute([':id'=>$idp]); $pret = $checkPack->fetchColumn(); if($pret===false){ $skip++; $errs[]="#$line pachet inexistent ($idp)"; continue; }
  $pret = (float)$pret;

  $get = function(string $k) use($row,$idx){ return isset($idx[$k]) ? cell((string)($row[$idx[$k]] ?? '')) : ''; };

  $email   = $get('email');
  $nume    = $get('nume');
  $prenume = $get('prenume');
  $telefon = $get('telefon');
  $npersS  = $get('numar_persoane'); $npers = ($npersS===''? null : (int)$npersS);
  if($npers===null || $npers<=0){ $npers = 1; } // minim 1

  // data_rezervare: CSV sau azi
  $data = $get('data_rezervare');
  if($data===''){ $data = date('Y-m-d'); }

  // adresa (NOT NULL în schema ta)
  $adresa = $get('adresa'); if($adresa===''){ $adresa='-'; }
  // adresa_fizica opțional – punem NULL
  $adresa_fizica = null;

  $judet  = $get('judet') ?: null;
  $local  = $get('localitate') ?: null;
  $metoda = map_plata($get('metoda_plata')); // NULL dacă nu recunoaște

  // total: CSV sau calcul
  $total = to_float($get('total'));
  if($total===null){ $total = (float)$npers * (float)$pret; }

  try{
    $existsRes->execute([':c'=>$idc, ':p'=>$idp]);
    $exists = (bool)$existsRes->fetchColumn();

    $params = [
      ':c'=>$idc, ':p'=>$idp,
      ':data'=>$data, ':npers'=>$npers, ':total'=>$total,
      ':adresa_fizica'=>$adresa_fizica, ':judet'=>$judet, ':localitate'=>$local, ':metoda'=>$metoda,
      ':mentiuni'=> null, ':tip'=> null,
      ':email'=>$email, ':nume'=>$nume, ':prenume'=>$prenume, ':telefon'=>$telefon, ':adresa'=>$adresa,
    ];

    if($exists){ $updRes->execute($params); $okU++; }
    else       { $insRes->execute($params); $okI++; }
  } catch(Throwable $e){
    $err++; $errs[]="#$line ".$e->getMessage();
  }
}
fclose($fh);
$pdo->commit();

$msg = "Import Rezervări: inserate $okI, actualizate $okU, sărite $skip";
if($err>0 || $skip>0){
  if(!empty($errs)){ $msg .= " | Detalii: ".implode('; ', array_slice($errs,0,10)).(count($errs)>10?' ...':''); }
  $_SESSION['errorMessage']=$msg;
} else {
  $_SESSION['successMessage']=$msg;
}
header('Location: manageReservations.php'); exit;
