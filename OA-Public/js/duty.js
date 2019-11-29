$(document).ready(function(){

	//值班人员管理
	$('#duty #dutyer_manage a').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Duty/dutyerManage','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//值班表管理
	$('#duty #dutytable_manage a').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Duty/dutyTableManage','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//值班表管理
	$('#dutyUrgent #dutyUrgent_manage a').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Duty/dutyUrgentManage','newwindow','height=600,width=850,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	
	$('.createDutyer').click(function(){
		var thisCreate = $(this);
		$.ajax({
			url:"/OA/index.php/Index/Duty/ajaxDutyer",
			type:'post',
			data:{leader:$(this).parent('td').find('input[type=hidden]').val()},
			dataType:'json',
			success:function(json){
				
				if(json.data != ''){
					thisCreate.parents('tr').find('td span[class=msg]').html(json.data);
				}else{
					alert('该领导的值班信息还没有创建，请在“值班人员管理”中添加');
				}
				
			},
			error:function(){
				// alert('error in dutyUrgent');
			}
		});
	});

	$('.createUrgentDutyer').click(function(e){
		var thisCreate = $(this);
		$.ajax({
			url:"/OA/index.php/Index/Duty/ajaxUrgentDutyer",
			type:'post',
			data:{date:$(this).parent('td').find('input[type=text]').val()},
			dataType:'json',
			success:function(json){
				// alert(json.data);
				if(json.status != 0){
					//alert(json.data['pos']);
					thisCreate.parents('tr').find('td span[class=msg_leader]').html(json.data['leader']);
					thisCreate.parents('tr').find('td span[class=msg_pos]').html(json.data['pos']);
					thisCreate.parents('tr').find('td textarea[use*=rec_save]').text(json.data['dutyer']);
					thisCreate.parents('tr').find('td input[use*=person_save]').val(json.data['dutyerids']);
				}else{
					alert('该日的日常值班表不存在，请先添加日常值班');
				}
				
			},
			error:function(){
				// alert('error in dutyUrgent');
			}
		});
	});

	$('.delete').click(function(e){
		if(!confirm('确定要删除吗？')){
			e.preventDefault();
		}
	});
});