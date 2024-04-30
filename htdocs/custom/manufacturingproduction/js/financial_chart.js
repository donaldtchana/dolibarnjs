var graphCat = 'b';
var invertCosts = false;
var type = 'n';
var period = 'm';
var totals = [];
var lbltotal;

var myChart;

$(document).ready(function () {
		for (var i = 0; i < colnums; i++) {
			totals.push(0);
		}
    createBar(type, period);
    $('input:radio[name=graphCat]').change(function () {
        graphCat = this.value;
        createGraph();
    });
    $('input:radio[name=graphType]').change(function () {
        type = this.value;
        createGraph();
    });
     $('#invertcosts').click(function() {
         invertCosts = $(this).is(':checked');
         createGraph();
    });
    $('input:radio[name=period]').change(function () {
        period = this.value;
        createGraph(type, period);
    });
});

function clearTotals() {
    for (var i = 0; i < colnums; i++) {
        totals[i] = 0;
    }
}

function setTotals(bgCol) {
	var linetotal = [];
	for (var i = 0; i < colnums; i++) {
		linetotal.push(totals[i]);
	}
	
    dataset = {
       label: lbltotal,
       data: linetotal,
       backgroundColor: bgCol
   };        
   myChart.data.datasets.push(dataset);
  
}

// Generate the pie graph data
function createPie(graphType) {
    var dataset; 
		var valToInsert;
		var firsttotal = true;		
		
    change('pie');
		myChart.data.datasets.length = 0;
    clearTotals();
		
    // For each row
		var bgCol = [];
		var pielabels = [];
		totals = [];
		monthTot = 0;
    for (var i = 0; i < values.length; i++) {
        
        var rowType = types[i];
        
        // If Graph type is normal or group, jump totals rows
        if (graphType == 'n') 
            noOk = 'tT';
        // If Graph type is total, shows onty totals rows (T)
        else if (graphType == 't') 
            noOk = 'tio';
        else 
            noOk = 'T';
                    
        if (noOk.includes(rowType)) 
            continue;
        
				// Totalize for month
				if (graphType == 'n')
					monthTot = 0;
        for (var j = 0; j < values[i]['data'].length; j++) {
            
						if (graphType == 't' || graphType == 'g') {
							// Costs in positive (groups & total)
							valToInsert = Math.abs(values[i]['data'][j]);
            } else if (!invertCosts && 'FOo'.includes(rowType)) {
							// Costs in positive (detail)
							valToInsert = values[i]['data'][j] *-1;
            } else {
							// No positive costs 
							valToInsert = values[i]['data'][j];
            }
						
						// Totalize
						monthTot += valToInsert;
        }
				
				// If graph type is "total" and the row is total, print the calculated totals
				if (graphType == 't' && rowType == 'T') {
						pielabels.push(values[i]['label']);
						totals.push(monthTot);
						bgCol.push(colors[i]);
						monthTot = 0;
				// If graph type is "group", totalize or print
				} else if (rowType == 't' && graphType == 'g') {
						if (!firsttotal) {
							totals.push(monthTot);
							pielabels.push(lbltotal);
							bgCol.push(colors[i]);
							lbltotal = values[i]['label'];
							monthTot = 0;
						} else 
							lbltotal = values[i]['label'];
						firsttotal = false;
				// If graph type is "detail", print the data						
        }  else if (graphType == 'n') {
						pielabels.push(values[i]['label']);
						totals.push(monthTot);
						bgCol.push(colors[i]);
				}
				
        
    }    
		if (graphType == 'g') {
			totals.push(monthTot);
			pielabels.push(lbltotal);
			bgCol.push(colors[i]);
		}	
		
		// Draw graph
		myChart.data.labels = pielabels; 
    setTotals(bgCol);
    myChart.update();
    
}

// Generate the bar graph data
function createBar(graphType, period) {
    var  dataset; 
		var firsttotal = true;
		var valToInsert;
		var modulus = 0;
		
		if (graphCat == 'b') 
			change('bar');
		else
			change('line');
    myChart.data.datasets.length = 0;
		clearTotals();
		
		if (period == 'm') {
			myChart.data.labels = labels;
		} else if (period == 'q') {
			myChart.data.labels = ['1° '+descQuarter, '2° '+descQuarter, '3° '+descQuarter, '4° '+descQuarter];
			modulus = 3;
		} else {
			myChart.data.labels = ['1° '+descSemester, '2°'+descSemester];
			modulus = 6;
		}
    
    // For each row
    for (var i = 0; i < values.length; i++) {
        
        var rowType = types[i];
        
        // If Graph type is normal or group, jump totals rows
        if (graphType == 'n') 
            noOk = 'tT';
        // If Graph type is total, shows onty totals rows
        else if (graphType == 't') 
            noOk = 'tio';
        // If graph is groups
				else 
            noOk = 'T';
                    
        if (noOk.includes(rowType)) 
            continue;
        
        // if group header
        if (rowType == 't' && graphType == 'g') {
						if (!firsttotal) {
							setTotals(bgCol);
							lbltotal = values[i]['label'];
						} else 
							lbltotal = values[i]['label'];
						clearTotals();
						firsttotal = false;
        }   
        
        // Set data and colors
        var bgCol = [];
        var data = [];
				
				// For each month
        for (var j = 0; j < colnums; j++) {
            bgCol.push(colors[i]);
            
						if (invertCosts && (graphType == 't' || graphType == 'g')) {
							// Costs in positive (groups & total)
							valToInsert = Math.abs(values[i]['data'][j]);
            } else if (!invertCosts && 'FOo'.includes(rowType)) {
							// Costs in positive (detail)
							valToInsert = values[i]['data'][j] *-1;
            } else {
							// No positive costs 
							valToInsert = values[i]['data'][j];
            }
						
						if (period == 'm') {
							// Monthly view
							data.push(valToInsert);
							totals[j] += valToInsert;
						} else {
							// Quarter or semestrial view
							if ((j % modulus) == 0) {
								data.push(valToInsert);
							} else {
								data[Math.floor(j/modulus)] += valToInsert;
							}
							totals[Math.floor(j/modulus)] += valToInsert;
						}
						
        }
        
        // If graph type is "total" and the row is total, print the calculated totals
				if (graphType == 't' && rowType == 'T') {
						lbltotal = values[i]['label'];
            setTotals(bgCol);
						clearTotals();
				}
				// If graph type is "detail", print the data
        else if (graphType == 'n') {
            dataset = {
                label: values[i]['label'],
                data: data,
                backgroundColor: bgCol,
								borderColor: bgCol,
            };        
            myChart.data.datasets.push(dataset);
        }
    }    
    
		// If Graph type is group, print last group data
    if (graphType == 'g') {
        setTotals(bgCol);
        clearTotals();
    }   
    
    myChart.update();
    
}

// Change the chart type
function change(newType) {
  var ctx = document.getElementById("myChart").getContext("2d");
  // Remove the old chart and all its event handles
  if (myChart) {
    myChart.destroy();
  }
  // Redraw chart
  var temp = jQuery.extend(true, {}, config);
  temp.type = newType;
	if (newType == 'pie') {
		$('#container').css('width', '60%');
	} else {
		$('#container').css('width', '100%');
	}
  myChart = new Chart(ctx, temp);
};

function createGraph() {
	if (graphCat == 'b' || graphCat == 'l')
		createBar(type, period);
	else
		createPie(type);
}

function printPDF() {
	var newCanvas = document.querySelector('#myChart');

  //create image from dummy canvas
  var newCanvasImg = newCanvas.toDataURL("image/png", 1.0);

  //creates PDF from img
  var doc = new jsPDF("l", "mm", "a4");
	var height = doc.internal.pageSize.getHeight();
	var width = 0;
	if (graphCat == 'b')
		width = doc.internal.pageSize.getWidth();
	else
		width = height;
  doc.addImage(newCanvasImg, 'JPEG', 0, 0, width, height);
  doc.save(title+'.pdf');
}
