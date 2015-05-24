
(function ($){
	$.fn.extend({
		'nnfelogin': function ( opt ) {
			
			var opt = $.extend({
				key:''
			}, opt);
			
			
			function encryptPassword ( str ) {
				if (!str) return '';
				var arr = [];
				for (var i = 0; i < str.length; i++) {
					arr.push( String.fromCharCode(opt.key.charCodeAt(i%opt.key.length)*1 + str.charCodeAt(i) - 48) );
				}
				return arr.join('');
			}
			
			return this.each(function () {
			
				var $me = $(this);
				
				$me.find('form').submit( function () {
					$me.find('input[type="password"]').each(function () {
						$(this).val( encryptPassword( $(this).val() ) );
					});
				});
				
			});
		}
	});
})(jQuery);