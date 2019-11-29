$(document).ready(function(){
	
	// alert('aa');
	//右侧框架中的页面自动跳转
	$('#top-2 a[href*=richang]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Notify/show_notify";
	});
	$('#top-2 a[href*=geren]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Notify/show_message";
	});
	$('#top-2 a[href*=huiyi]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Meet/search";
	});
	$('#top-2 a[href*=wenjian]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/File/show_file?flag=1";
	});
	$('#top-2 a[href*=zhiban]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Duty/search";
	});
	$('#top-2 a[href*=xinfang]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Petition/petition_search?flag=1";
	});
	$('#top-2 a[href*=ducha]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Inspect/show_inspect?flag=3&flag_index=1&dep=1";
	});
	$('#top-2 a[href*=renshi]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Personnel/show_all_record";
	});
	$('#top-2 a[href*=laozi]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Labor/itemApply_index";
	});
	$('#top-2 a[href*=xuexi]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Study/view";
	});
	$('#top-2 a[href*=quanxian]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Rbac/index";
	});
	$('#top-2 a[href*=xinxi]').click(function(e){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Information/information_index";
	});

	






	
	$('#top-2 a').click(function(){
		$('#top-2 span').removeClass('span_selected');
		$(this).parents('span').addClass('span_selected');
	});

	//主页新闻图片轮转
	for(var j=1;j<5;j++){
		$('#pic img').eq(j).hide();
		$('#ddpic a').eq(j).hide();
	}
	$('#pic ul li').click(function(){
		var i = $(this).text()-1;
		$('#pic img').filter(":visible").fadeOut(500).parent().children('img').eq(i).fadeIn(1000);
		$('#ddpic a').filter(":visible").fadeOut(500).parent().children().eq(i).fadeIn(1000);
		$(this).toggleClass('on');
		$(this).siblings().removeAttr("class");
	}); 
	$('#pic').everyTime('4s',round);
	$('#pic ul li').hover(
		function(){
			$('#pic').stopTime();
		}, 
		function(){
			$('#pic').everyTime('4s',round);
		}
	);
	var t=1;
	function round(){
		if(t>=5) t = t - 5;
		$('#pic ul li').eq(t).click();
		t++;
	}
	
	//ajax操作,定时读取待办事项
	$('#hidden_newMess').everyTime('1s','A',flush,0,true);
	function flush(){
		var t = false;
		$.ajax({
			url:"/OA/index.php/Index/Notify/listenMess",
			type:'post',
			dataType:'json',
			success:function(json){
				if(json.status == 1){
					if(!json.data[1]){
						var msg = '<ul id="newMessDialog">';
						$.each(json.data[2],function(index,val){
							msg += '<li>'+val+'</li>';
						});
						msg+='</ul>';
						msg+='<span id="newMessDialogHint">您可以在待办事项中查看详情</span>';
						showDialog('confirm',msg,'您有新消息',300,jumpToMessPage,0);
					}
					$('#newMess').html("【<a href='/OA/index.php/Index/Notify/moreMessage?from=newMess' id='look'>新消息["+json.data[0]+"]</a>】");
				}else{
					$('#newMess').html("【新消息[0]】");
				}
			},
			error:function(){
				// alert('error in message');
			}

		});
	}

	//ajax操作,定时读取未读邮件
	$('#hidden_mail').everyTime('3s','B',flush_mail,0,true);
	function flush_mail(){
		$.ajax({
			url:"/OA/index.php/Index/Mail/listenMail",
			type:'post',
			dataType:'json',
			success:function(json){
				if(json.status != 0){
					var title = '您有'+json.status+'封新邮件';
					var msg = '';
					$.each(json.data,function(index,val){
							msg += '<div><span>发件人：</span>'+val[0]+'<br>';
							msg += '<span>时间:</span>'+val[1]+'<br>';
							msg += '<span>主题：</span>'+val[2]+'</div><hr>';
						
					});
					msg+='<span>您可以在邮箱中查看详情</span>';
					showDialog('confirm',msg,title,300,jumpToMessPage,0);
				}
			},
			error:function(){
				// alert('error in mail');
			}

		});
	}
	function jumpToMessPage(a){
		var afr = window.parent.document.getElementById('right').contentDocument;
		afr.location = "/OA/index.php/Index/Notify/show_message";
	}


	//会议审核不通过
	$('#deny_form').hide();
	$('#deny').click(function(){
		$('#deny_form').show();
	});

	//会议安排表
	$('#nextweek').hide();
	$('#week').click(function(){
		if($('#week').val() == 1){
			$('#nextweek').hide();
			$('#thisweek').show();
		}else{
			$('#nextweek').show();
			$('#thisweek').hide();
		}
	});
	//ajax后台对会议安排进行特定条件查询
	$('#key_search_btn').click(function(){
		$.ajax({
			url:"/OA/index.php/Index/Meet/keysearch",
			type:'post',
			dataType:'json',
			data:{
				s_y:$('#keyword_search select[name*="startTime[Year]"]').val(),
				s_m:$('#keyword_search select[name*="startTime[Month]"]').val(),
				s_d:$('#keyword_search select[name*="startTime[Day]"]').val(),
				e_y:$('#keyword_search select[name*="endTime[Year]"]').val(),
				e_m:$('#keyword_search select[name*="endTime[Month]"]').val(),
				e_d:$('#keyword_search select[name*="endTime[Day]"]').val(),
				attend_leader:$('#keyword_search input[name=attend_leader]').val(),
				apply_department:$('#keyword_search select[name=apply_department]').val()
			},
			success:function(json){
				$('#keyword_search #msg').html(json.data);
			},
			error:function(){
				// alert('error');
			}

		});
	});

	//ajax后台对局外会议进行特定条件查询
	$('#outmeet_search #search').click(function(){
		$.ajax({
			url:"/OA/index.php/Index/Meet/outkeysearch",
			type:'post',
			dataType:'json',
			data:{
				s_y:$('#outmeet_search select[name*="s_time[Year]"]').val(),
				s_m:$('#outmeet_search select[name*="s_time[Month]"]').val(),
				s_d:$('#outmeet_search select[name*="s_time[Day]"]').val(),
				e_y:$('#outmeet_search select[name*="e_time[Year]"]').val(),
				e_m:$('#outmeet_search select[name*="e_time[Month]"]').val(),
				e_d:$('#outmeet_search select[name*="e_time[Day]"]').val(),
			
				s_from:$('#outmeet_search input[name=s_from]').val(),
				s_leader:$('#outmeet_search input[name=s_leader]').val()
			},
			success:function(json){
				$('#outmeet_search #msg').html(json.data);
			},
			error:function(){
				// alert('error');
			}

		});
	});

	// 信访单
	$('#petition1 #modify').click(function(){
		$('.detail #petition2').show();
		$('#petition1 #no_modify').hide();
	});

	//上报信息不通过
	$('#info_deny_reason').hide();
	$('#info_deny').click(function(e){
		e.preventDefault();
		$('#info_deny_reason').show();
	});

	//其他链接部分的js
	$(".mian8-in2 ul:not(:eq(0))").hide();
	$(".mian8-in1 ul li").removeAttr('id');
	$(".mian8-in1 ul li:eq(0)").attr('id','opt_select');
	$('.mian8-in1 ul li').hover(function(){
		var seq = $(this).attr('seq');
		$(".mian8-in2 ul:not(:eq("+seq+"))").hide();
		$(".mian8-in1 ul li").removeAttr('id');
		$(".mian8-in2 ul:eq("+seq+")").show();
		$(".mian8-in1 ul li:eq("+seq+")").attr('id','opt_select');
	},
	function(){
		
	}
	);

	$('.delete').click(function(e){
		if(!confirm('确定要删除吗？')){
			e.preventDefault();
		}
	});

	$('.contact').click(function(e){
		e.preventDefault();
		var cla = $(this).parent('td').find('textarea').attr('class');
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

	$('.newContact').click(function(e){
		e.preventDefault();

		var cla = $(this).parent('td').find('textarea').attr('class');
		var g_id = $(this).parent('td').find('input[name*=group_id]').val();
		var ind_id = $(this).parent('td').find('input[name*=individual_id]').val();

		// alert(cla);
		$.layer({
		    type: 2,
		    shadeClose: true,
		    title: '通讯录',
		    closeBtn: [0, true],
		    shade: [0.2, '#000'],
		    border: [0],
		    offset: ['100px',''],
		    area: ['500px', '350px'],
		    bgcolor:'#fff',		   
		    iframe: {src: '/OA/index.php/Index/Common/newContact?inputClass='+cla+'&g_id='+g_id+'&ind_id='+ind_id}    
		});
	});

	// 值班管理
	$('#dutyUrgent #tableNodifyManage #addOneRow').click(function(e){
		e.preventDefault();
		var clone = $('#dutyUrgent #tableNodifyManage #first').clone(true);
		var l = clone.find('td:last');
		var l_p = l.prev('td');
		var l_p_p = l_p.prev('td');
		l.text('未填写');
		l_p.text('未填写');
		l_p_p.text('未填写');
		var num = $('#dutyUrgent #tableNodifyManage tr').length - 2;

		//改变一下 带班领导input 的类名
		// clone.find('td:eq(2)').find('input[class*=contact_input]').attr('class','contact_input_leader'+num);
		clone.removeAttr('id').find('td').eq(0).text(num);
		//改变class的名称
		var cla = clone.find('textarea').attr('class');
		
		var tmp_arr = new Array();
		var tmp_arr = cla.split('_');
		var new_cla = tmp_arr[0]+'_'+tmp_arr[1]+'_'+num;
		//增加下面一行，更改领导的选择框class，added by zhaoteng at 2015-07-16 18:57
		var new_cla_two = 'contact_input' + num;
		clone.find('input[class*=contact_input]').attr('class',new_cla_two);
		/////////////////////////////////////////////
		clone.find('textarea').attr('class',new_cla);
		clone.insertBefore('#dutyUrgent #tableNodifyManage #last');
	});
	$('#dutyUrgent #tableNodifyManage #deleteOneRow').click(function(e){
		e.preventDefault();
		var del = $('#dutyUrgent #tableNodifyManage #last').prev('tr');
		if(!del.is('#dutyUrgent #tableNodifyManage #first')){
			del.remove();
		}
	});
	$('#dutyUrgent input[name=time]').blur(function(){
		$.ajax({
			url:"/OA/index.php/Index/Duty/ajaxWeek",
			type:'post',
			data:{time:$('#dutyUrgent input[name=time]').val()},
			dataType:'json',
			success:function(json){
				$('#dutyUrgent #week').text(json.data);
			},
			error:function(){
				// alert('error in dutyUrgent');
			}
		});
	});
	
	
	$('.contact_input').everyTime('500ms',changeTextarea);

	function changeTextarea(){
		
		var value = $('.contact_input').val()
		var len = value.length;
		var sum = 0;
		for(var i=0;i<len;i++){
			if(value[i].match(/[^\x00-\xff]/ig) != null){
				sum += 13;
			}else{
				sum += 8;
			}
		}
		var x = $('.contact_input').height() / 15 - 1;
		if(sum-400*x > 350){
			var h = $('.contact_input').height();
			h = h + 15;
			$('.contact_input').height(h);
		}
		var y = $('.contact_input').height()/15 -1;
		if(y*400 - sum > 400){
			var diff = (y*400-sum)/400;
			var h = $('.contact_input').height();
			h = h - diff*15;
			$('.contact_input').height(h);
		}
	}
	// 新通讯录
	$('textarea[class*=newContact_input]').everyTime('500ms',newChangeTextarea);

	function newChangeTextarea(){
		
		var value = $('textarea[class*=newContact_input]').val()
		var len = value.length;
		var sum = 0;
		for(var i=0;i<len;i++){
			if(value[i].match(/[^\x00-\xff]/ig) != null){
				sum += 13;
			}else{
				sum += 8;
			}
		}
		var x = $('textarea[class*=newContact_input]').height() / 15 - 1;
		if(sum-400*x > 350){
			var h = $('textarea[class*=newContact_input]').height();
			h = h + 15;
			$('textarea[class*=newContact_input]').height(h);
		}
		var y = $('textarea[class*=newContact_input]').height()/15 -1;
		if(y*400 - sum > 400){
			var diff = (y*400-sum)/400;
			var h = $('textarea[class*=newContact_input]').height();
			h = h - diff*15;
			$('textarea[class*=newContact_input]').height(h);
		}
	}
	// 新通讯录
	// $('.newContact_input').everyTime('500ms',newChangeTextarea);

	// function newChangeTextarea(){
		
	// 	var value = $('.newContact_input').val()
	// 	var len = value.length;
	// 	var sum = 0;
	// 	for(var i=0;i<len;i++){
	// 		if(value[i].match(/[^\x00-\xff]/ig) != null){
	// 			sum += 13;
	// 		}else{
	// 			sum += 8;
	// 		}
	// 	}
	// 	var x = $('.newContact_input').height() / 15 - 1;
	// 	if(sum-400*x > 350){
	// 		var h = $('.newContact_input').height();
	// 		h = h + 15;
	// 		$('.newContact_input').height(h);
	// 	}
	// 	var y = $('.newContact_input').height()/15 -1;
	// 	if(y*400 - sum > 400){
	// 		var diff = (y*400-sum)/400;
	// 		var h = $('.newContact_input').height();
	// 		h = h - diff*15;
	// 		$('.newContact_input').height(h);
	// 	}
	// }

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

	// 规章
	$('#reg_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Regulation/manageRegulation','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	// 文件上传
	$('#addOutFile').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/File/addOutFile','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	// 人事管理 
	$('#personnel #civilRecord').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/record?flag=civil','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #publicRecord').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/record?flag=public','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #civilJudge').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/judge?flag=civil','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #publicJudge').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/judge?flag=public','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #quarterJudgeGather').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/quarterJudgeGather','newwindow','height=400,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	$('#personnel #addCheckAttendance').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/addCheckAttendance','newwindow','height=600,width=1500,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #addAskLeave').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/addAskLeave','newwindow','height=600,width=1200,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #quarterManage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/quarterManage','newwindow','height=600,width=1000,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #checkAttManage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/checkAttManage','newwindow','height=600,width=1600,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #askLeaveManage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/askLeaveManage','newwindow','height=600,width=1600,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#personnel #personnelInfoManage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/personnelInfoManage','newwindow','height=600,width=1600,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	//修改会议室
	$('#manageMeetPlace').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Meet/manageMeetPlace','newwindow','height=400,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});


	$('#cj').hide();
	$('#pr').hide();
	$('#pj').hide();

	$('#personnel_select').click(function(e){
		if($(this).val() == 'cr'){
			$('#cr').show();
			$('#cj').hide();
			$('#pr').hide();
			$('#pj').hide();
		}else if($(this).val() == 'cj'){
			$('#cr').hide();
			$('#cj').show();
			$('#pr').hide();
			$('#pj').hide();
		}else if($(this).val() == 'pr'){
			$('#cr').hide();
			$('#cj').hide();
			$('#pr').show();
			$('#pj').hide();
		}else if($(this).val() == 'pj'){
			$('#cr').hide();
			$('#cj').hide();
			$('#pr').hide();
			$('#pj').show();
		}
	});

	// 备忘录
	$('#memo #myMemo').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Notify/myMemo','newwindow','height=500,width=650,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//页面加载即判断有没有备忘录到提醒时间，用ajax
	$.ajax({
		url:"/OA/index.php/Index/Notify/ajaxMemo",
		type:'post',
		dataType:'json',
		success:function(json){
			if(json.status != 0){
				var title = '您有'+json.status+'条备忘';
				var msg = '';
				$.each(json.data,function(index,val){
						msg += '<div><span>时间：</span>'+val['time']+'<br>';
						msg += '<span>标题:</span>'+val['title']+'<br>';
						msg += '<span>内容：</span>'+val['content']+'</div><hr>';
				});
				msg+='<span>您可以在“我的备忘录”中查看详情</span>';
				showDialog('confirm',msg,title,300);
			}
		},
		error:function(){
			// alert('error in memo');
		}
	});

	$('#contact #search #contact_search').click(function(){
		if($('#contact #search input[name=name]').val() == ''){
			alert('请输入姓名');
			return;
		}
		$.ajax({
			url:"/OA/index.php/Index/Contact/ajaxSearch",
			type:'post',
			data:{name:$('#contact #search input[name=name]').val()},
			dataType:'json',
			success:function(json){
				$('#contact #search #msg').html(json.data);
			},
			error:function(){
				// alert('error in contact');
			}
		});
	});

	// 管理通讯录
	$('#contact #contact_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Contact/manageContact','newwindow','height=500,width=850,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//添加人事信息详情
	$('#personnel #personnelInfo').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Personnel/addPersonnelInfo','newwindow','height=500,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	//通知公告管理
	$('#content #notify_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Notify/notifyManage','newwindow','height=600,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	

	//通讯录选择时 删除最后一个输入
	$('.delContactLastInput').click(function(e){
		e.preventDefault();
		var con1 = $(this).parent('td').find('textarea[class*=contact_input]');
		var con2 = $(this).parent('td').find('input[type=hidden]');
		var last1 = con1.val().lastIndexOf(',');
		var last2 = con2.val().lastIndexOf(',');
		con1.val(con1.val().substring(0,last1));
		con2.val(con2.val().substring(0,last2));
	});
    //reset 清空隐藏域
    $('input[type=reset]').click(function(){
    	$(this).parents('form').find('.hidden').val('');
    });

    //修改密码和修改个人信息
   $('#individual #alterPwd').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Individual/alterPwd','newwindow','height=300,width=400,top=200px,left=400px,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
   $('#individual #alterInfo').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Individual/alterInfo','newwindow','height=500,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
   
   //工作动态管理
	$('#work #work_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Work/work_manage','newwindow','height=600,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//图片新闻管理
	$('#news #news_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/News/news_manage','newwindow','height=600,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//共享园地管理
	$('#study #study_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Study/study_manage','newwindow','height=600,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//会议室管理
	$('#meet_apply #meet_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Meet/meet_manage','newwindow','height=600,width=700,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	//督察事项管理
	$('#inspect #inspect_manage').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Inspect/inspect_manage','newwindow','height=600,width=900,top=0,left=0,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	
	//个人文档
	$('#profile #scope').hide();
	$('#profile select[name=type]').click(function(){
		// alert($(this).val());
		if($(this).val()==1)
			$('#profile #scope').hide();
		else
			$('#profile #scope').show();
	});

	$('.rollback').click(function(e){
		if(!confirm("该操作不可撤销，确定要退回到上一环节吗？")){
			e.preventDefault();
		}
	});
	
	$('.agreeRep').click(function(e){
		if(!confirm("确定要同意上报吗？")){
			e.preventDefault();
		}
	});

	$('#foot_contactUs').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Common/contactUs','newwindow','height=300,width=300,top=200,left=300,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#foot_privacy').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Common/privacy','newwindow','height=300,width=500,top=200,left=300,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});
	$('#foot_copyright').click(function(e){
		e.preventDefault();
		window.open('/OA/index.php/Index/Common/copyright','newwindow','height=300,width=500,top=200,left=300,toolbar=no,menubar=no,scrollbars=yes,resizable=no,location=no,status=no');
	});

	
});