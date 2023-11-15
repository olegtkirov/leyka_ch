jQuery(document).ready(function($) {
	var $data = {}; let id=''; let count = 0; let error=0;
	$("#btnSubmit").prop("disabled",true);
	$('div#report').hide();
	$("form#ch_form").change(function(e) {
		e.preventDefault(); // отменяем стандартное поведение браузера
		$('div#report').empty();
//		var pd = this.name;
//		$("form#ch_form input, form#ch_form select").find("input:checkbox select").each(function() {// проверяем каждое поле в форме
		$("form#ch_form input:checkbox, form#ch_form select").each(function() {// проверяем каждое поле в форме
// добавим новое свойство к объекту $data
// имя свойства – значение атрибута name элемента
// значение свойства – значение свойство value элемента
//$data[this.id] = $(this).val(); parseFloat()
			count = $(':checkbox:checked').length;
//			var id = $(this).attr('value');
			id = $(this).val();
//			id = typeof id == 'undefined' ? false : id;
//			if( typeof id === "undefined" ){
			if( count < 1 ){
				// add to error messages, set error classes, etc
				error = 1;
				$('div#report').text('Не выбраны закрытые кампании.');
			} 
			$('div#report').show();
			if ($('form#ch_form select option').is(':selected')) { $('div#report').html('Активная кампания выбрана.'); }
			//if ($('form#ch_form select option').not(':selected')) { $('div#report').html('Активная кампания не выбрана.'); }

			if (count > 0 ) {
				$('div#report').html("Выбрано закрытых кампании с активными подписками - "+count);
			}
//			$('div#report').append('Find Form - '+id+ ', Err: '+ error +' = '+count+'<br />');
		});
		if (count > 0) {
			$("#btnSubmit").prop("disabled",false);
			$("#btnSubmit").css({'color':'white','background':'#2271B1'});
		}
	});
	$("form#ch_form").submit(function(event){
	if (count >= 1 && confirm("Подтверждаете перенос?")) { alert("Да. Перенести!") } else {  alert("Отмена переноса. Не всё выбрано.");
	  return false;
	}
	});
});