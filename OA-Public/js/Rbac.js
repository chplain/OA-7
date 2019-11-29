$(document).ready(function(){

	$('#setAccess input[level=1]' ).click(function(){
		if($(this).is(':checked') == true){
			$(this).parents('.level1').find('input[type=checkbox]').each(function(){
				this.checked = true;
			});
		}else{
			$(this).parents('.level1').find('input[type=checkbox]').removeAttr('checked');
		}
		
	});
	$('#setAccess input[level=2]' ).click(function(){
		if($(this).is(':checked') == true){
			$(this).parents('dl').find('input[type=checkbox]').each(function(){
				this.checked = true;
			});
			$(this).parents('.level1').find('input[level=1]').each(function(){
				this.checked = true;
			});
		}else{
			$(this).parents('dl').find('input[type=checkbox]').removeAttr('checked');
		}
		
	});
	$('#setAccess input[level=3]' ).click(function(){		
		if($(this).is(':checked') == true){
			$(this).parents('dl').find('input[level=2]').each(function(){
				this.checked = true;
			});
			$(this).parents('.level1').find('input[level=1]').each(function(){
				this.checked = true;
			});
		}
	});
	

	$('#addUser #clone').click(function(e){
		e.preventDefault();
		var clone = $(this).parent('td').parent('tr').clone();
		clone.removeAttr('id');
		clone.find('#clone').remove();
		clone.find('#dele_clone').remove();
		clone.insertBefore('#addUser table tr:last');
	});
	$('#addUser #dele_clone').click(function(e){
		e.preventDefault();
		var remove = $('#addUser table tr:last').prev();
		if(!remove.is('#addUser #first_role')){
			remove.remove();
		}
	});

	$('.delete').click(function(e){
		if(!confirm('确定要删除吗？')){
			e.preventDefault();
		}
	});

});