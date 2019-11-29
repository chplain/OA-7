$(document).ready(function(){
	$('#right .middle .content .list table tr:even:not()').addClass('even');
	$('#right .middle .content .list table tr:odd').addClass('odd');
	$('#right .middle .content .list table tr:last-child').addClass('last_tr');


	$('#publish').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Information/publish','newwindow','height=500,width=800,top=100,left=200,toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no');
	});

	$('#report').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Information/addreport','newwindow','height=550,width=800,top=50,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('#appro').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Information/addAppro','newwindow','height=550,width=800,top=50,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('#propa').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Information/addPropa','newwindow','height=450,width=800,top=50,left=200,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('#close').click(function(){
		window.close();
	});

	//上报信息不通过
	$('#info_deny_reason').hide();
	$('#info_deny').click(function(e){
		e.preventDefault();
		$('#info_deny_reason').show();
	});

	$('#index_search').click(function(){
		$.ajax({
			url:"/OA/index.php/Index/Information/search",
			type:'post',
			dataType:'json',
			data:{
				dep:$('#index_table select[name=dep]').val(),
				isReported:$('#index_table input[name=isReported]:checked').val()
			},
			success:function(json){
				$('#index_table #msg').html(json.data);
			},
			error:function(){
				// alert('error');
			}

		});
	});

	$('#delete').click(function(e){
		if(confirm('确认要删除吗？')){
			// 什么都不做
		}else{
			e.preventDefault();
		}
	});
	$('.delete').click(function(e){
		if(confirm('确认要删除吗？')){
			// 什么都不做
		}else{
			e.preventDefault();
		}
	});

	

});