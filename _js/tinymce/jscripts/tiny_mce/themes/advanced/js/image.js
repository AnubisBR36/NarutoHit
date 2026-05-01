var ImageDialog = {
	preInit : function() {
		var url;

		tinyMCEPopup.requireLangPack();

		if (url = tinyMCEPopup.getParam("external_image_list_url"))
			document.write('<script language="javascript" type="text/javascript" src="' + tinyMCEPopup.editor.documentBaseURI.toAbsolute(url) + '"></script>');
	},

	init : function() {
		var f = document.forms[0], ed = tinyMCEPopup.editor;

		// Setup browse button
		document.getElementById('srcbrowsercontainer').innerHTML = getBrowserHTML('srcbrowser','src','image','theme_advanced_image');
		if (isVisible('srcbrowser'))
			document.getElementById('src').style.width = '180px';

		e = ed.selection.getNode();

		this.fillFileList('image_list', 'tinyMCEImageList');

		if (e.nodeName == 'IMG') {
			f.src.value = ed.dom.getAttrib(e, 'src');
			f.alt.value = ed.dom.getAttrib(e, 'alt');
			f.border.value = this.getAttrib(e, 'border');
			f.vspace.value = this.getAttrib(e, 'vspace');
			f.hspace.value = this.getAttrib(e, 'hspace');
			
			// Aplicar lógica condicional para dimensões existentes
			var currentWidth = ed.dom.getAttrib(e, 'width');
			var currentHeight = ed.dom.getAttrib(e, 'height');
			
			if (currentWidth && currentHeight) {
				if (parseInt(currentWidth) > 500 || parseInt(currentHeight) > 400) {
					f.width.value = "500";
					f.height.value = "400";
				} else {
					f.width.value = currentWidth;
					f.height.value = currentHeight;
				}
			} else {
				f.width.value = "500";
				f.height.value = "400";
			}
			
			f.insert.value = ed.getLang('update');
			this.styleVal = ed.dom.getAttrib(e, 'style');
			selectByValue(f, 'image_list', f.src.value);
			selectByValue(f, 'align', this.getAttrib(e, 'align'));
			this.updateStyle();
		} else {
			// Para novas imagens, fixar inicialmente em 500x400
			f.width.value = "500";
			f.height.value = "400";
		}
		
		// Sempre desabilitar os campos de dimensão e adicionar mensagem
		f.width.disabled = true;
		f.height.disabled = true;
		
		// Adicionar mensagem informativa se não existir
		var dimensionsContainer = f.width.parentNode;
		if (!document.getElementById('dimensions-message')) {
			var messageSpan = document.createElement('span');
			messageSpan.id = 'dimensions-message';
			messageSpan.style.color = '#666';
			messageSpan.style.fontSize = '11px';
			messageSpan.style.display = 'block';
			messageSpan.style.marginTop = '3px';
			messageSpan.innerHTML = 'Tamanho automático: máximo 500x400 pixels (mantém original se menor)';
			dimensionsContainer.appendChild(messageSpan);
		}
	},

	fillFileList : function(id, l) {
		var dom = tinyMCEPopup.dom, lst = dom.get(id), v, cl;

		l = window[l];

		if (l && l.length > 0) {
			lst.options[lst.options.length] = new Option('', '');

			tinymce.each(l, function(o) {
				lst.options[lst.options.length] = new Option(o[0], o[1]);
			});
		} else
			dom.remove(dom.getParent(id, 'tr'));
	},

	update : function() {
		var f = document.forms[0], nl = f.elements, ed = tinyMCEPopup.editor, args = {}, el;

		tinyMCEPopup.restoreSelection();

		if (f.src.value === '') {
			if (ed.selection.getNode().nodeName == 'IMG') {
				ed.dom.remove(ed.selection.getNode());
				ed.execCommand('mceRepaint');
			}

			tinyMCEPopup.close();
			return;
		}

		if (!ed.settings.inline_styles) {
			args = tinymce.extend(args, {
				vspace : nl.vspace.value,
				hspace : nl.hspace.value,
				border : nl.border.value,
				align : getSelectValue(f, 'align')
			});
		} else
			args.style = this.styleVal;

		tinymce.extend(args, {
			src : f.src.value,
			alt : f.alt.value,
			width : f.width.value,
			height : f.height.value
		});

		el = ed.selection.getNode();

		if (el && el.nodeName == 'IMG') {
			ed.dom.setAttribs(el, args);
		} else {
			ed.execCommand('mceInsertContent', false, '<img id="__mce_tmp" />', {skip_undo : 1});
			ed.dom.setAttribs('__mce_tmp', args);
			ed.dom.setAttrib('__mce_tmp', 'id', '');
			ed.undoManager.add();
		}

		tinyMCEPopup.close();
	},

	updateStyle : function() {
		var dom = tinyMCEPopup.dom, st, v, f = document.forms[0];

		if (tinyMCEPopup.editor.settings.inline_styles) {
			st = tinyMCEPopup.dom.parseStyle(this.styleVal);

			// Handle align
			v = getSelectValue(f, 'align');
			if (v) {
				if (v == 'left' || v == 'right') {
					st['float'] = v;
					delete st['vertical-align'];
				} else {
					st['vertical-align'] = v;
					delete st['float'];
				}
			} else {
				delete st['float'];
				delete st['vertical-align'];
			}

			// Handle border
			v = f.border.value;
			if (v || v == '0') {
				if (v == '0')
					st['border'] = '0';
				else
					st['border'] = v + 'px solid black';
			} else
				delete st['border'];

			// Handle hspace
			v = f.hspace.value;
			if (v) {
				delete st['margin'];
				st['margin-left'] = v + 'px';
				st['margin-right'] = v + 'px';
			} else {
				delete st['margin-left'];
				delete st['margin-right'];
			}

			// Handle vspace
			v = f.vspace.value;
			if (v) {
				delete st['margin'];
				st['margin-top'] = v + 'px';
				st['margin-bottom'] = v + 'px';
			} else {
				delete st['margin-top'];
				delete st['margin-bottom'];
			}

			// Merge
			st = tinyMCEPopup.dom.parseStyle(dom.serializeStyle(st));
			this.styleVal = dom.serializeStyle(st);
		}
	},

	getAttrib : function(e, at) {
		var ed = tinyMCEPopup.editor, dom = ed.dom, v, v2;

		if (ed.settings.inline_styles) {
			switch (at) {
				case 'align':
					if (v = dom.getStyle(e, 'float'))
						return v;

					if (v = dom.getStyle(e, 'vertical-align'))
						return v;

					break;

				case 'hspace':
					v = dom.getStyle(e, 'margin-left')
					v2 = dom.getStyle(e, 'margin-right');
					if (v && v == v2)
						return parseInt(v.replace(/[^0-9]/g, ''));

					break;

				case 'vspace':
					v = dom.getStyle(e, 'margin-top')
					v2 = dom.getStyle(e, 'margin-bottom');
					if (v && v == v2)
						return parseInt(v.replace(/[^0-9]/g, ''));

					break;

				case 'border':
					v = 0;

					tinymce.each(['top', 'right', 'bottom', 'left'], function(sv) {
						sv = dom.getStyle(e, 'border-' + sv + '-width');

						// False or not the same as prev
						if (!sv || (sv != v && v !== 0)) {
							v = 0;
							return false;
						}

						if (sv)
							v = sv;
					});

					if (v)
						return parseInt(v.replace(/[^0-9]/g, ''));

					break;
			}
		}

		if (v = dom.getAttrib(e, at))
			return v;

		return '';
	},

	resetImageData : function() {
		var f = document.forms[0];

		f.width.value = f.height.value = "";	
	},

	updateImageData : function() {
		var f = document.forms[0], t = ImageDialog;

		// Verificar se a imagem foi carregada
		if (t.preloadImg && t.preloadImg.width && t.preloadImg.height) {
			var originalWidth = t.preloadImg.width;
			var originalHeight = t.preloadImg.height;
			
			// Se a imagem for maior que 500x400, fixar em 500x400
			if (originalWidth > 500 || originalHeight > 400) {
				f.width.value = "500";
				f.height.value = "400";
			} else {
				// Se for menor ou igual, manter o tamanho original
				f.width.value = originalWidth;
				f.height.value = originalHeight;
			}
		} else {
			// Fallback para 500x400 se não conseguir carregar a imagem
			f.width.value = "500";
			f.height.value = "400";
		}
		
		// Desabilitar os campos para que não possam ser alterados
		f.width.disabled = true;
		f.height.disabled = true;
	},

	getImageData : function() {
		var f = document.forms[0];

		this.preloadImg = new Image();
		this.preloadImg.onload = this.updateImageData;
		this.preloadImg.onerror = this.resetImageData;
		this.preloadImg.src = tinyMCEPopup.editor.documentBaseURI.toAbsolute(f.src.value);
	}
};

ImageDialog.preInit();
tinyMCEPopup.onInit.add(ImageDialog.init, ImageDialog);