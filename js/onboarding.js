jQuery(function($){
	$(document).ready(function(){
		$(".contentify-ai-ob-form").each(function(){
			var wrap = $(this);
			var railEl = wrap.find(".caiobf-step-rail");
			var pagesEl = wrap.find(".caiobf-uo-pages");
			var optimizedEl = wrap.find(".caiobf-uo-optimized");
			var optimizedPEl = wrap.find(".caiobf-optimized-percent");
			var step1F = wrap.find(".caiobf-step-1");
			var step2F = wrap.find(".caiobf-step-2");
			var step3F = wrap.find(".caiobf-step-3");
			var step4F = wrap.find(".caiobf-step-4");

			function updateApiKey(){
				$.ajax({
					type: "POST",
					url: CAIOBData.ajaxUrl3,
					data: {
						caiobf_api_key: step1F.find(".caiobf-api-key").val().trim(),
						cai_nonce: wrap.find("input[name='cai_nonce']").val()
					},
					cache: false,
					timeout: 2 * 60 * 1000
				});
			}

			function optimizeObject(pages, index){
				$.ajax({
					type: "POST",
					url: CAIOBData.ajaxUrl2,
					data: {
						type: pages.eq(index).data("type"),
						id: pages.eq(index).data("id"),
						title: pages.eq(index).data("title"),
						cai_nonce: wrap.find("input[name='cai_nonce']").val()
					},
					cache: false,
					timeout: 2 * 60 * 1000
				}).done(function(results){
					optimizedEl.append(CAIOBData.itemTpl.replaceAll("%title%", pages.eq(index).data("title")).replaceAll("%type%", pages.eq(index).data("type")).replaceAll("%status%", results));
				}).always(function(){
					var percent = Math.round(10000*(index+1)/pages.length)/100;
					optimizedPEl.text(percent);
					if(percent >= 100){
						setTimeout(function(){
							document.location.href = CAIOBData.redirectUrl;
						},2000);
						return false;
					}
					if(index + 1 < pages.length) {
						optimizeObject(pages, index+1);
					}
				});
			}

			function populateList(e = null){
				if(e != null){
					e.preventDefault();
				}
				railEl.animate({marginLeft: "-200%"});
				step3F.addClass("doing-ajax");
				$.ajax({
					type: "POST",
					url: CAIOBData.ajaxUrl,
					data: wrap.find(":input").serialize(),
					cache: false,
					timeout: 2 * 60 * 1000
				}).done(function(results){
					var html = "";
					for(var i = 0; i < results.length; i++){
						html += CAIOBData.pageTpl.replaceAll("%title%", results[i].title).replaceAll("%type%", results[i].type).replaceAll("%id%", results[i].id);
					}
					pagesEl.html(html);
				}).always(function(){
					step3F.removeClass("doing-ajax");
				});
			}

			if(wrap.hasClass("contentify-ai-skip")){
				railEl.css({marginLeft: "-200%"});
				populateList();
			}

			wrap.animate({"opacity":1});

			step1F.find(".caiobf-btn").on("click", function(e){
				e.preventDefault();
				updateApiKey();
				if(step1F.find(".caiobf-api-key").val().trim() == "") {
					return false;
				}
				railEl.animate({marginLeft: "-100%"});
			});

			step2F.find(".caiobf-btn-proceed").on("click", function(e){
				populateList(e);
			});

			step3F.find(".caiobf-btn-black").on("click", function(e){
				e.preventDefault();
				pagesEl.html("-");
				railEl.animate({marginLeft: "-100%"});
			});

			step3F.on("click", ".caiobf-btn-exclude", function(e){
				e.preventDefault();
				$(this).closest(".caiobf-uo-page").removeClass("caiobf-uo-page-included").addClass("caiobf-uo-page-excluded");
			});

			step3F.on("click", ".caiobf-btn-include", function(e){
				e.preventDefault();
				$(this).closest(".caiobf-uo-page").addClass("caiobf-uo-page-included").removeClass("caiobf-uo-page-excluded");
			});

			step3F.on("click", ".caiobf-btn-optimize", function(e){
				e.preventDefault();
				var pages = pagesEl.hide().find(".caiobf-uo-page-included");
				var total = pages.length;
				var index = 0;
				if(total < 1){
					return false;
				}
				$("html, body").animate({scrollTop: 0},"50");
				railEl.animate({marginLeft: "-300%"}, 700, function(){
					optimizeObject(pages, index);
				});
			});
		});
	});
});