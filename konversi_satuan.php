<?php

class UnitConverter
{
    protected $number;
    
    protected $select = [];
    
    protected $units = [];
    
    protected $numbers = [];
    
    protected $exchanges = [];
    
    protected $formulation = [];
    
    public function __construct($number)
    {
        $this->number = $number;
    }
    
    public function select($first, $second)
    {
        $this->select = [$first, $second];
        
        return $this;
    }
    
    public function unit(array $unit)
    {
        $this->units[] = $unit;
        
        return $this;
    }
    
    public function number($number)
    {
        $this->numbers[] = $number;
        
        return $this;
    }
    
    public function exchange(array $exchange)
    {
        if (count($exchange) === 2) {
            $this->exchanges[] = $exchange;
        }
        
        return $this;
    }
    
    public function formulation(array $formula)
    {
        if (count($formula) === 1 || count($formula) === 2) {
            foreach ($formula as $value) {
                if (! is_callable($value)) {
                    break;
                }
            }
            
            $this->formulation = $formula;
        }
        
        return $this;
    }
    
    public function convert($flat = false)
    {
        $units = $this->units;
        $numbers = $this->numbers;
        $unitOrders = [
            $this->_unitOrder($units, $this->select[0]),
            $this->_unitOrder($units, $this->select[1]),
        ];
        
        if (count($units) !== count($numbers) ||
            $unitOrders[0] === false || $unitOrders[1] === false
        ) {
            return false;
        }
        
        $conversion = 0;
        if ($unitOrders[0][0] !== $unitOrders[1][0] &&
            ! empty($this->exchanges) && count($this->exchanges) > 0
        ) {
            $formulation = $this->formulation[1] ?? null;
            foreach ($this->exchanges as $exchange) {
                if (count($exchange) !== 2) {
                    break;
                }
                
                $formula = [];
                foreach ($exchange as $value) {
                    if (is_int($get = array_search($value, $units[$unitOrders[0][0]])) && ! isset($formula[0])) {
                        $formula[0] = [$unitOrders[0][0], $get];
                    }
                    
                    if (is_int($get = array_search($value, $units[$unitOrders[1][0]])) && ! isset($formula[1])) {
                        $formula[1] = [$unitOrders[1][0], $get];
                    }
                }
                
                if (! empty($formula)) {
                    $result = $this->number * pow($numbers[$unitOrders[0][0]], $formula[0][1]) / pow($numbers[$unitOrders[0][0]], $unitOrders[0][1]);
                    $conversion = $result *  pow($numbers[$unitOrders[1][0]], $unitOrders[1][1]) / pow($numbers[$unitOrders[1][0]], $formula[1][1]);
                    
                    if (is_callable($formulation)) {
                        return $formulation($this->number, $numbers, $unitOrders, $formula, $flat);
                    }
                    
                    break;
                }
            }

            $conversion = is_numeric($conversion) && floor($conversion) != $conversion
                ? rtrim(sprintf('%.f', $conversion), 0) : $conversion;

            return $flat === false ? sprintf('%s %s', $conversion, $this->select[1]) : $conversion;
        }
        
        if (isset($this->formulation[0]) && is_callable($this->formulation[0])) {
            return $this->formulation[0]($this->number, $numbers, $unitOrders, $flat);
        }
        
        $conversion = $this->number * pow($numbers[$unitOrders[1][0]], $unitOrders[1][1]) / pow($numbers[$unitOrders[0][0]], $unitOrders[0][1]);
        $conversion = is_numeric($conversion) && floor($conversion) != $conversion
            ? rtrim(sprintf('%.f', $conversion), 0) : $conversion;
            
        return $flat === false ? sprintf('%s %s', $conversion, $this->select[1]) : $conversion;
    }
    
    private function _unitOrder(array $units, $select)
    {
        foreach ($units as $index => $unit) {
            if (is_int($value = array_search($select, $unit))) {
                return [$index, $value];
            } elseif (is_array($select) && count($select) === 2 && count($units) < $select[0]) {
                if (isset($unit[$select[1]])) {
                    return [$index, $unit[$select[1]]];
                }
            } elseif (isset($unit[$select])) {
                return [$index, $unit[$select]];
            }
        }
        
        return false;
    }
}

// // Volume
// $satuan = new UnitConverter(8);
// $satuan->unit(['kl', 'hl', 'dal', 'l', 'dl', 'cl', 'ml'])->number(10);
// $satuan->unit(['km', 'hm', 'dam', 'm', 'dm', 'cm', 'mm'])->number(pow(10, 3));
// $satuan->exchange(['cm', 'ml']);
// $satuan->select('m', 'dl');
// echo $satuan->convert(), "\n";

// // Panjang
// $satuan = new UnitConverter(8000);
// $satuan->unit(['km', 'hm', 'dam', 'm', 'dm', 'cm', 'mm'])->number(10);
// $satuan->select('cm', 'km');
// echo $satuan->convert(), "\n";

// // Berat
// $satuan = new UnitConverter(800);
// $satuan->unit(['kg', 'hg', 'dag', 'g', 'dg', 'cg', 'mg'])->number(10);
// $satuan->select('g', 'kg');
// echo $satuan->convert(), "\n";

// // Luas
// $satuan = new UnitConverter(5);
// $satuan->unit(['km', 'hm', 'dam', 'm', 'dm', 'cm', 'mm'])->number(pow(10, 2));
// $satuan->unit(['hektare', 'are'])->number(pow(10, 2));
// $satuan->exchange(['are', 'dam']);
// $satuan->select('km', 'hektare');
// echo $satuan->convert(), "\n";

// // Inch
// $satuan = new UnitConverter(25.4);
// $satuan->unit(['km', 'hm', 'dam', 'm', 'dm', 'cm', 'mm'])->number(10);
// $satuan->select('mm', 'cm');
// echo $satuan->convert(true) * 40, "\n";


$satuan = new UnitConverter(20);
$satuan->unit(['kaki'])->number(12); // kaki ke inch
$satuan->unit(['inch'])->number(1 / 12); // inch ke kaki
$satuan->exchange(['inch', 'kaki']);
$satuan->select('kaki', 'inch');
// $satuan->select('inch', 'kaki');
$satuan->formulation([1 => function ($amount, $numbers, $orders, $formula, $flat) {
    return $orders[0][0] === 0
        ? $amount * $numbers[$orders[0][0]]
        : $amount / $numbers[$orders[0][1]];
}]);
echo $satuan->convert(true), "\n";
