$(document).ready(function(){
	$('#addOneRow').click(function(e){
		e.preventDefault();
		var clone = $('#first_tr').clone();
		//计算有多少行
		var len = $('.input_row').length;
		//设置index
		clone.find('td:eq(0) div').text(len+1);
		//清空value
		clone.find('input[type=text]').val('');
		//去除id='first_tr'
		clone.removeAttr('id');
		clone.insertBefore($('#remark_tr'));
	});
	$('#delOneRow').click(function(e){
		e.preventDefault();
		var clone = $('#remark_tr').prev('tr');
		if(!clone.is('#first_tr')){
			clone.remove();
		}
	});

	$('table tr').eq(1).next('tr').find('td div').addClass('STYLE2');
	$('table tr').eq(1).next('tr').next('tr').find('td div').addClass('STYLE2');
	$('table tr').eq(1).next('tr').next('tr').find('td div').addClass('STYLE3');

	// 通讯录
	$('input[class*=contact_input]').click(function(e){
		e.preventDefault();
		var cla = $(this).attr('class');
		$.layer({
		    type: 2,
		    shadeClose: true,
		    title: '通讯录',
		    closeBtn: [0, true],
		    shade: [0.2, '#000'],
		    border: [0],
		    offset: ['100px',''],
		    area: ['300px', '350px'],
		    bgcolor:'#fff',
		    iframe: {src: '/OA/index.php/Index/Common/contact?inputClass='+cla}
		    
		    
		});
	});
});