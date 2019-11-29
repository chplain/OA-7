$(document).ready(function(){
	var reurl = window.location.href;
	var r = window.location.search;
	
	var index = r.indexOf('=')+1;
	var endIndex = r.indexOf('&');
	if(endIndex == -1)
		endIndex = r.length;
	//class名
	var sub = r.substring(index,endIndex);
	//group_id
	var argv1 = r.substring(endIndex+1,r.length);

	var endIndex2 = argv1.indexOf('&');
	
	var g_id = argv1.substring(5,endIndex2);
	// alert(g_id);
	var g_id_arr = new Array();
	g_id_arr = g_id.split(',');
	var has0 = g_id_arr.indexOf('0');
	// alert(has0);
	//individual_id
	var argv2 = argv1.substring(endIndex2+1,argv1.length);
	var ind_id = argv2.substring(7,argv2.length);
	
	var newArr = new Array();
	newArr = sub.split('_');
	var sub_index = newArr[2];
	

	//显示已经选好的科室或人员*++*
	$.ajax({
		url:"/OA/index.php/Index/Common/ajaxDisplay",
		type:'post',
		dataType:'json',
		data:{
			has0:has0,
			group:g_id,
			individual:ind_id
		},
		success:function(json){
			$('#right #msg_group').html(json.data['group']);
			$('#right #msg_individual').html(json.data['individual']);
		},
		error:function(){
			// alert('error');
		}
	});


	$('#individual').hide();
	
	$('input:radio[name=group]').click(function(){
		var r = $(this).val();
		if(r == 1){
			$('#individual').hide();
			$('#group').show();
		}else if(r == 0){
			$('#individual').show();
			$('#group').hide();
		}
	});

	$('.st_tree span').hide();
	$(".st_tree").SimpleTree({
		
	});


	

	$('#group a').click(function(e){
		e.preventDefault();
		var a_name = $(this).text();
		// if($(this).attr('res') == 0)
		// 	a_name = '<a href="#" onclick="remove();" res="'+$(this).attr('res')+'">'+a_name+'<br/></a>';
		// else
			a_name = '<a href="#" onclick="remove();" res="'+$(this).attr('res')+'">'+a_name+'(科室-'+$(this).attr('res')+')'+'<br/></a>';
		var msg_group = $('#right #msg_group').html();
		
		$('#right #msg_group').html(msg_group+a_name);
		
	});
	$('#individual a').click(function(e){
		e.preventDefault();
		var a_name = $(this).text();
		a_name = '<a href="#" onclick="remove();" res="'+$(this).attr('res')+'">'+a_name+'(个人-'+$(this).attr('res')+')'+'<br/></a>';
		var msg_individual = $('#right #msg_individual').html();
		$('#right #msg_individual').html(msg_individual+a_name);
		
	});
	

	// 取消
	$('#cancel').click(function(){
		$('#msg_group').html('');
		$('#msg_individual').html('');
	});

	//获取当前窗体索引
	var index = parent.layer.getFrameIndex(); 
	
	//确认选择
	$('#confirm').click(function(){
		
		$.ajax({
			url:"/OA/index.php/Index/Common/ajaxNewContact",
			type:'post',
			dataType:'json',
			data:{
				group:$('#msg_group').text(),
				individual:$('#msg_individual').text()
			},
			success:function(json){
				
				$('textarea[class='+sub+']',window.parent.document).html(json.data['group_name']+json.data['individual_name']);
				$('textarea[class='+sub+']',window.parent.document).parent('td').find('input[name*=group_id]').val(json.data['group_id']);
				$('textarea[class='+sub+']',window.parent.document).parent('td').find('input[name*=individual_id]').val(json.data['individual_id']);
				
				//$('input[name=group_id_'+sub_index+']',window.parent.document).val(json.data['group_id']);
				//$('input[name=individual_id_'+sub_index+']',window.parent.document).val(json.data['individual_id']);
				parent.layer.close(index);
			},
			error:function(){
				// alert('error');
			}

		});

		
	});
	
});
