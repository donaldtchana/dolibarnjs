<?php
include_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";

function getPeriodDesc($date, $criteria) {
    global $langs;
    switch ($criteria) {
        case 'weekly': 
            $date = strtotime($date);
            $out = date("W", $date).'/'.date("y", $date);            
            break;
        case 'monthly': 
            $date = strtotime($date);
            $out = $langs->trans(date("M", $date)).' '.date("y", $date);
            break;
        case 'quarterly': 
            $current_quarter = ceil(date("m", strtotime($date)) / 3);
            $out = $current_quarter.'°'.$langs->trans('Quarter').' '.date("Y", strtotime($date));
            break;
        case 'annual': 
            $out = date("Y", strtotime($date));            
            break;
    }
    return $out;
}

print '<table class="border centpercent tableforfieldcreate">'."\n";
print '<tr style="line-height:25px">';
print '<td>';


print '<canvas id="myChart" width="400" height="160"></canvas>';
print '<div>';
print '    <div style="float:left;">';
print '        <input type="radio" id="bar" name="graphCat" value="b" checked="">';
print '        <label for="bar">'.$langs->trans('Bar').'</label>';
print '        <input type="radio" id="pie" name="graphCat" value="p">';
print '        <label for="pie">'.$langs->trans('Pie').'</label><br>';
print '    </div>';
print '    <div align="right" style="float:right;">';    
print '    <input type="submit" class="button" name="btn_print" value="'.$langs->trans("ExportPDF").'" onclick="printPDF()">';
print '    </div>';        
print '</div>   ';

$dateStart=date('Y-m-d H:i:s', PHP_INT_MAX); $dateEnd=0;; 

// Create the aValues array with the grouped and totalized data, set the titles
$aValues = array();
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
        $obj = $db->fetch_object($resql);
        
        // Get the group value
        if ($type == 'costcenter')
            $title = $obj->label;
        else
            $title = $obj->title;
        
        // Ordinates titles
        if ($printPeriodTot) {
            $periodTitle = getPeriodDesc($obj->date, $totalizePeriod);
            if (strtotime($obj->date) < $dateStart)
                $dateStart = strtotime ($obj->date);
            if (strtotime ($obj->date)> $dateEnd)
                $dateEnd = strtotime ($obj->date);
        } else {
            
        }
        
        $amount = $aValues[$title][$periodTitle]; 
        $aValues[$title][$periodTitle] += ($obj->type == 2 ? $obj->qty*$obj->amount : -($obj->qty * $obj->amount));
        $i++;
    }

}

// Set $dateStart to the beginning of the period
switch ($totalizePeriod) {
    case 'weekly': 
        $dateStart= strtotime('monday this week', $dateStart);
        break;
    case 'monthly': 
        $dateStart= strtotime('first day of this month', $dateStart);
        break;
    case 'quarterly': 
        $current_quarter = ceil(date("m", $dateStart) / 3);
        $date = date('Y-m-t', strtotime(date('Y', $dateStart) . '-' . ((($current_quarter-1)* 3)) . '-1'));
        $dateStart= strtotime("+1 day", strtotime($date));
        break;
    case 'annual': 
        $dateStart= strtotime('first day of january', $dateStart);
        break;
}
        
// Create the array of periods between first and last date
$alabels=array();
$labels = "var labels = ['";
if ($printPeriodTot) {                                                          // Graph with periods
    $current_date = $dateStart;
    while($current_date < $dateEnd)  {
        $key = getPeriodDesc(date("Y-m-d",$current_date), $totalizePeriod);
        $alabels[] = $key;
        $labels .= $key."','";
        switch ($totalizePeriod) {
            case 'weekly': 
                $current_date= strtotime("+1 week", $current_date);
                break;
            case 'monthly': 
                $current_date= strtotime("+1 month", $current_date);
                break;
            case 'quarterly': 
                $current_date= strtotime("+3 month", $current_date);
                break;
            case 'annual': 
                $current_date= strtotime("+1 year", $current_date);
                break;
        }
    }
    if (count($alabels) == 0) {
        $key = getPeriodDesc(date("Y-m-d",$current_date), $totalizePeriod);
        $alabels[] = $key;
        $labels .= $key."'];"."\n";
    } else
        $labels = rtrim($labels, ",'")."'];"."\n";  


    $bgCol = array();
    $values = array();
    $types = array();

    // Scan the $aValues array and match with the periods
    foreach ($aValues as $alabel => $avalue) {
        $label = $alabel;
        $result = array();
        foreach ($alabels as $key) { 
            if (array_key_exists($key, $avalue))
                $result[] = floatval($avalue[$key]);
            else
                $result[] = 0;
        }

        //$result[] = floatval($value);
        $bgCol[] = '#'.randomColor();
        $values[] = array('label'=>$label, 'data'=>$result);
        $type = 'A';
        $types[] = $type;

    }
} else {                                                                        // Graph without periods

    $bgCol = array();
    $values = array();
    $types = array();

    // Scan the $aValues array and match with the periods
    $result = array();
    $label = 'pippo';
    foreach ($aValues as $alabel => $avalue) {
        //$labels .= $alabel."','";
        $result = array(floatval($avalue['']));
        $bgCol[] = '#'.randomColor();
        $values[] = array('label'=>$alabel, 'data'=>$result);
        $types[] = 'A';
    }
    
    if ($type == 'costcenter')
        $labels .= $langs->trans('CostCenter')."']"."\n";
    else
        $labels .= $langs->trans('Project')."']"."\n";
}

?>

<script>
   
<?php 
    // Create js variables
    echo $labels; 
    echo "var values = ".json_encode($values).";"."\n";
    echo "var colors = ".json_encode($bgCol).";"."\n";
    echo "var types = ".json_encode($types).";"."\n";
    echo "var descQuarter = '".$langs->trans('Quarter')."';"."\n";
    echo "var descSemester = '".$langs->trans('Semester')."';"."\n";
    echo "var title = '".$object->label."';"."\n";
?>

var ctx = document.getElementById('myChart').getContext('2d');
var colnums = values.length;
var config = {
    type: 'bar',
    data: {
        labels: labels,
    },
    options: {
     scales: {
            y: {beginAtZero: true }
        },
        //locale : 'it-IT',
        plugins: {
            title: {
                display: true,
                text: title,
                fullSize: true
            },
						
        }
			
    }
};


</script>
<?php
