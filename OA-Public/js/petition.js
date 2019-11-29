$(document).ready(function(){

	
	$('#report_type').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Petition/ajaxReportClass",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					// msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					if(val['child'].length != 0)
						msg += ' '+val['name']+'<br/>';
					else
						msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					$.each(val['child'],function(index2,val2){
						msg += '&nbsp;&hellip;<input type="radio" name="selectClass" value="'+val2['name']+'('+val2['id']+')'+'">'+ val2['name']+'<br/>';
					});
				});
				showDialog('confirm',msg,'请选择分类',300,fn);

			},
			error:function(){
				// alert('error in MaintainClass');
			}
		});
	});
	function fn(){
		var v = $('input[name=selectClass]:checked').val();
		$('input[name=report_type]').val(v);
	}
	$('#modifyReportType').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Petition/reportClass','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('#petition_search #p_search_btn').click(function(){
		//首先判断三个选择人员的输入框是不是为空
		var input1 = $('#petition_search #search_table .contact_input_1').val();
		var input2 = $('#petition_search #search_table .contact_input_2').val();
		var input3 = $('#petition_search #search_table .contact_input_3').val();
		if(input1 == '') $('#petition_search #search_table .contact_input_1').parent('td').find('input[type=hidden]').val('');
		if(input2 == '') $('#petition_search #search_table .contact_input_2').parent('td').find('input[type=hidden]').val('');
		if(input3 == '') $('#petition_search #search_table .contact_input_3').parent('td').find('input[type=hidden]').val('');
		$.ajax({
			url:"/OA/index.php/Index/Petition/keysearch",
			type:'post',
			dataType:'json',
			data:{
				dep:$('#p_search select[name=department]').val(),
				method:$('#p_search select[name=method]').val(),
				recv_time:$('#p_search input[name=recv_time]').val(),
				turn_time:$('#p_search input[name=turn_time]').val(),
				should_time:$('#p_search input[name=should_time]').val(),
				done_time:$('#p_search input[name=done_time]').val(),
				report_type:$('#p_search input[name=report_type]').val(),
				type:$('#p_search select[name=type]').val(),
				town:$('#p_search input[name=town]').val(),
				receiver:$('#p_search input[name=receiver]').val(),
				dep_receiver:$('#p_search input[name=dep_receiver]').val(),
				trasactor:$('#p_search input[name=trasactor]').val()
			},
			success:function(json){
				$('#petition_search #msg').html(json.data);
			},
			error:function(){
				// alert('error');
			}

		});
	});

	$('#petition_search #done_time_type').hide();
		$('#petition_search select[name=display_type]').click(function(){
			if($(this).val() == 1){
				$('#done_time_type').hide();
				$('#recv_time_type').show();
			}else{
				$('#recv_time_type').hide();
				$('#done_time_type').show();
			}
		});
	
	//乡镇
	$('#town_select').click(function(e){
		e.preventDefault();
		var msg = '';
		$.ajax({
			url:"/OA/index.php/Index/Petition/ajaxTown",
			type:'post',
			dataType:'json',
			success:function(json){
				$.each(json.data,function(index,val){
					msg += '<input type="radio" name="selectClass" value="'+val['name']+'('+val['id']+')'+'">'+ val['name']+'<br/>';
					
				});
				showDialog('confirm',msg,'请选择乡镇',300,fn2);

			},
			error:function(){
				// alert('error in town');
			}
		});
	});
	function fn2(){
		var v = $('input[name=selectClass]:checked').val();
		$('input[name=town]').val(v);
	}
	//添加乡镇
	$('#petition #add_town').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Petition/add_town','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//信访单管理
	$('#petition #petition_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Petition/petition_manage','newwindow','height=500,width=1000,top=100,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
});