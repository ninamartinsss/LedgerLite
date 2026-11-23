<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/Ledger.php';
function eq(mixed $expected,mixed $actual):void{if($expected!==$actual)throw new RuntimeException('Expected '.var_export($expected,true).' got '.var_export($actual,true));}
$db=new Ledger(':memory:');
$income=$db->add(['type'=>'income','amount_cents'=>150000,'category'=>'Salary','description'=>'Monthly','occurred_on'=>'2026-11-20']);
$expense=$db->add(['type'=>'expense','amount_cents'=>3750,'category'=>'Food','occurred_on'=>'2026-11-21']);
eq(1,$income['id']);eq(3750,$expense['amount_cents']);eq(146250,$db->summary('2026-11')['balance_cents']);
eq(3750,$db->summary('2026-11')['expense_by_category_cents']['Food']);eq(0,count($db->all('2026-10')));
try{$db->add(['type'=>'expense','amount_cents'=>-10,'category'=>'Food','occurred_on'=>'2026-11-21']);throw new RuntimeException('Expected invalid amount');}catch(InvalidArgumentException $e){}
try{$db->add(['type'=>'income','amount_cents'=>100,'category'=>'Salary','occurred_on'=>'2026-02-30']);throw new RuntimeException('Expected invalid date');}catch(InvalidArgumentException $e){}
eq(true,$db->delete($expense['id']));eq(false,$db->delete($expense['id']));eq(150000,$db->summary()['balance_cents']);echo "All Ledger assertions passed.\n";