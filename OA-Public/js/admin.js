$(document).ready(function(){
	$('#addDynamic').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addDynamic','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#addNotify').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addNotify','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#addImpwork').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addImpwork','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#addImpfile').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addImpfile','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#addSchedule').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addSchedule','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#addSummary').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/DepartAdmin/addSummary','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#closeWindow').click(function(e){
		e.preventDefault();
		window.close();
	});

	// 实现多个文件同时上传
	$('.removeInputFile').hide();
	$('.addInputFile').click(function(e){
		e.preventDefault();
		$('.removeInputFile').show();
		var lastspan = $(this).parent('td').find('span:last');
		var firstspan = $(this).parent('td').find('span:first');
		var clone = firstspan.clone(true);
		clone.insertAfter(lastspan);
		
	});
	$('.removeInputFile').click(function(e){
		e.preventDefault();
		var len = $(this).parents('td').find('span').length;
		if(len<=2){
			$('.removeInputFile').hide();
		}
		$(this).parents('span').remove();
	});

	$('.delete').click(function(e){
		if(!confirm('确定要删除吗？')){
			e.preventDefault();
		}
	});


});