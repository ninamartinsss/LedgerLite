<?php
declare(strict_types=1);
final class Ledger {
 private PDO $db;
 public function __construct(string $path) {
  if (!in_array('sqlite',PDO::getAvailableDrivers(),true)) throw new RuntimeException('PDO SQLite extension is required.');
  $this->db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $this->db->exec('PRAGMA foreign_keys = ON');
  $this->db->exec('CREATE TABLE IF NOT EXISTS transactions (id INTEGER PRIMARY KEY AUTOINCREMENT,type TEXT NOT NULL CHECK(type IN (\'income\',\'expense\')),amount_cents INTEGER NOT NULL CHECK(amount_cents > 0),category TEXT NOT NULL,description TEXT NOT NULL DEFAULT \'\',occurred_on TEXT NOT NULL,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
  $this->db->exec('CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(occurred_on)');
 }
 public function add(array $input):array {
  $type=$input['type']??null;$amount=$input['amount_cents']??null;
  $category=trim((string)($input['category']??''));$description=trim((string)($input['description']??''));$date=$input['occurred_on']??null;
  if(!in_array($type,['income','expense'],true))throw new InvalidArgumentException('type must be income or expense');
  if(!is_int($amount)||$amount<=0||$amount>999999999999)throw new InvalidArgumentException('amount_cents must be a positive integer');
  if($category===''||strlen($category)>80)throw new InvalidArgumentException('category must contain 1-80 characters');
  if(strlen($description)>250)throw new InvalidArgumentException('description too long');
  if(!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))throw new InvalidArgumentException('occurred_on must be YYYY-MM-DD');
  $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
  if(!$dt||$dt->format('Y-m-d')!==$date)throw new InvalidArgumentException('invalid calendar date');
  $stmt=$this->db->prepare('INSERT INTO transactions(type,amount_cents,category,description,occurred_on) VALUES(?,?,?,?,?)');
  $stmt->execute([$type,$amount,$category,$description,$date]);
  return $this->get((int)$this->db->lastInsertId());
 }
 public function get(int $id):array {
  $stmt=$this->db->prepare('SELECT id,type,amount_cents,category,description,occurred_on FROM transactions WHERE id=?');
  $stmt->execute([$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
  if(!$row)throw new OutOfBoundsException('transaction not found');
  $row['id']=(int)$row['id'];$row['amount_cents']=(int)$row['amount_cents'];return $row;
 }
 public function all(?string $month=null):array {
  $sql='SELECT id,type,amount_cents,category,description,occurred_on FROM transactions';$args=[];
  if($month!==null){$this->validateMonth($month);$sql.=' WHERE substr(occurred_on,1,7)=?';$args[]=$month;}
  $stmt=$this->db->prepare($sql.' ORDER BY occurred_on DESC,id DESC');$stmt->execute($args);
  $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach($rows as &$row){$row['id']=(int)$row['id'];$row['amount_cents']=(int)$row['amount_cents'];}
  return $rows;
 }
 public function delete(int $id):bool {
  $stmt=$this->db->prepare('DELETE FROM transactions WHERE id=?');$stmt->execute([$id]);return $stmt->rowCount()>0;
 }
 public function summary(?string $month=null):array {
  $transactions=$this->all($month);$income=0;$expense=0;$byCategory=[];
  foreach($transactions as $row){if($row['type']==='income')$income+=$row['amount_cents'];else{$expense+=$row['amount_cents'];$byCategory[$row['category']]=($byCategory[$row['category']]??0)+$row['amount_cents'];}}
  ksort($byCategory);
  return ['month'=>$month,'income_cents'=>$income,'expense_cents'=>$expense,'balance_cents'=>$income-$expense,'expense_by_category_cents'=>$byCategory,'transaction_count'=>count($transactions)];
 }
 private function validateMonth(string $month):void {
  if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$month))throw new InvalidArgumentException('month must be YYYY-MM');
 }
}