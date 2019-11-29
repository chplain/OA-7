$(document).ready(function(){
	$('#stockTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/stock','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#stockClass').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/stockClass','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('.modifyStock_btn').click(function(e){
		e.preventDefault();
		var t = $(this).attr('vid');
		window.open('/OA/index.php/Index/Labor/modifyStock?id='+t,'newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#itemTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/itemApply?from=check0','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#itemClassSet').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/itemClass','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#maintainTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/maintain','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('.modifyMaintain_btn').click(function(e){
		e.preventDefault();
		var t = $(this).attr('vid');
		window.open('/OA/index.php/Index/Labor/modifyMaintain?id='+t,'newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#vehicleMaintainTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/vehicleMaintain','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#OilTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/vehicleOil','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#ETCTable').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/vehicleETC','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#plateManage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Labor/plateManage','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('.modifyVehicleMaintain_btn').click(function(e){
		e.preventDefault();
		var t = $(this).attr('vid');
		window.open('/OA/index.php/Index/Labor/modifyVehicleMaintain?id='+t,'newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('.modifyOil_btn').click(function(e){
		e.preventDefault();
		var t = $(this).attr('vid');
		window.open('/OA/index.php/Index/Labor/modifyOil?id='+t,'newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('.modifyETC_btn').click(function(e){
		e.preventDefault();
		var t = $(this).attr('vid');
		window.open('/OA/index.php/Index/Labor/modifyETC?id='+t,'newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	//库存分类选择
	$('#selectStockClass').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Labor/ajaxStockClass",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					$.each(val['child'],function(index2,val2){
						msg += '&nbsp;&hellip;<input type="radio" name="selectClass" value="'+val2['name']+'('+val2['id']+')'+'">'+ val2['name']+'<br/>';
						$.each(val2['child'],function(index3,val3){
							msg += '&nbsp;&hellip;&hellip;&hellip;<input type="radio" name="selectClass" value="'+val3['name']+'('+val3['id']+')'+'">'+ val3['name']+'<br/>';
							$.each(val3['child'],function(index4,val4){
								msg += '&nbsp;&hellip;&hellip;&hellip;&hellip;&hellip;<input type="radio" name="selectClass" value="'+val4['name']+'('+val4['id']+')'+'">'+ val4['name']+'<br/>';
							});
						});
					});
				});
				showDialog('confirm',msg,'请选择分类',300,fn);

			},
			error:function(){
				// alert('error in stockClass');
			}
		});
		
	});
	function fn(){
		var v = $('input[type=radio]:checked').val();
		$('#stock input[name=class]').val(v);
	}

	//物品申领分类选择
	$('#selectItemClass').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Labor/ajaxItemClass",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					$.each(val['child'],function(index2,val2){
						msg += '&nbsp;&hellip;<input type="radio" name="selectClass" value="'+val2['name']+'('+val2['id']+')'+'">'+ val2['name']+'<br/>';
						$.each(val2['child'],function(index3,val3){
							msg += '&nbsp;&hellip;&hellip;&hellip;<input type="radio" name="selectClass" value="'+val3['name']+'('+val3['id']+')'+'">'+ val3['name']+'<br/>';
							$.each(val3['child'],function(index4,val4){
								msg += '&nbsp;&hellip;&hellip;&hellip;&hellip;&hellip;<input type="radio" name="selectClass" value="'+val4['name']+'('+val4['id']+')'+'">'+ val4['name']+'<br/>';
							});
						});
					});
				});
				showDialog('confirm',msg,'请选择分类',300,fn2);

			},
			error:function(){
				// alert('error in ItemClass');
			}
		});
		
	});
	function fn2(){
		var v = $('input[name=selectClass]:checked').val();
		$('#itemApply input[name=class]').val(v);
	}
	//设备维修分类
	$('#selectMaintainClass').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Labor/ajaxMaintainClass",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					$.each(val['child'],function(index2,val2){
						msg += '&nbsp;&hellip;<input type="radio" name="selectClass" value="'+val2['name']+'('+val2['id']+')'+'">'+ val2['name']+'<br/>';
					});
				});
				showDialog('confirm',msg,'请选择分类',300,fn3);

			},
			error:function(){
				// alert('error in MaintainClass');
			}
		});
	});
	function fn3(){
		var v = $('input[name=selectClass]:checked').val();
		$('#itemApply input[name=class]').val(v);
	}

	//车牌号选择
	$('#selectPlate').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Labor/ajaxPlate",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					msg += '<input type="radio" name="selectClass" value="'+val['platenum']+'('+val['id']+')'+'">'+ val['platenum']+'<br/>';
				});
				showDialog('confirm',msg,'请选择车牌号',300,fn4);

			},
			error:function(){
				// alert('error in selectPlate');
			}
		});
	});
	function fn4(){
		var v = $('input[name=selectClass]:checked').val();
		$('#itemApply input[name=platenum]').val(v);
	}



	$('#close').click(function(e){
		e.preventDefault();
		window.close();
	});
	$('.delete').click(function(e){
		if(confirm("确认要删除吗？")){}
		else{
			e.preventDefault();
		}
	});

	//common_list中当鼠标放到某一项上时
	// $('.common_list .common_list_content').hover(
	// 	function(){
	// 		$(this).css('background','#EAF9FF');

	// 	},
	// 	function(){
	// 		$(this).css('background','#D1EEEE');
	// 	}
	// );
});