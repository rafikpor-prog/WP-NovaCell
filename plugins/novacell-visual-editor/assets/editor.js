/* NOVACELL Visual Editor 0.2.0 — an extension of Gutenberg, not a second page format. */
(function (wp) {
  'use strict';
  if (!wp || !wp.plugins || !wp.element || !wp.blocks || !wp.data || !wp.components || !wp.blockEditor) { return; }
  var el = wp.element.createElement;
  var useState = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var useSelect = wp.data.useSelect;
  var useDispatch = wp.data.useDispatch;
  var components = wp.components;
  var blockEditor = wp.blockEditor;
  var settings = window.NovaCellVisualConfig || {};
  var Sidebar = (wp.editor && wp.editor.PluginSidebar) || (wp.editPost && wp.editPost.PluginSidebar);
  if (!Sidebar) { return; }

  var tiles = [
    { id: 'paragraph', label: 'Tekst', icon: 'type' },
    { id: 'heading', label: 'Nagłówek', icon: 'heading' },
    { id: 'image', label: 'Zdjęcie', icon: 'image' },
    { id: 'gallery', label: 'Galeria', icon: 'images' },
    { id: 'columns', label: 'Dwie kolumny', icon: 'columns' },
    { id: 'buttons', label: 'Przycisk', icon: 'mouse-pointer' },
    { id: 'list', label: 'Lista', icon: 'list' },
    { id: 'details', label: 'Pytania i odpowiedzi', icon: 'faq' },
    { id: 'section', label: 'Sekcja NOVACELL', icon: 'layers' },
    { id: 'group', label: 'Sekcja z tłem', icon: 'layout' },
    { id: 'quote', label: 'Cytat', icon: 'quote' },
    { id: 'separator', label: 'Linia oddzielająca', icon: 'minus' }
  ];

  function createTileBlock(id) {
    var create = wp.blocks.createBlock;
    switch (id) {
      case 'paragraph': return create('core/paragraph', { placeholder: 'Wpisz treść...' });
      case 'heading': return create('core/heading', { level: 2, placeholder: 'Tytuł sekcji' });
      case 'image': return create('core/image');
      case 'gallery': return create('core/gallery');
      case 'columns': return create('core/columns', {}, [create('core/column'), create('core/column')]);
      case 'buttons': return create('core/buttons', {}, [create('core/button', { text: 'Dowiedz się więcej' })]);
      case 'list': return create('core/list');
      case 'details': return create('core/details', { summary: 'Jak możemy pomóc?' }, [create('core/paragraph', { content: 'Odpowiedź na pytanie.' })]);
      case 'section': return create('novacell/section', { sectionId: 'hero', language: 'auto' });
      case 'group': return create('core/group');
      case 'quote': return create('core/quote');
      case 'separator': return create('core/separator');
      default: return null;
    }
  }

  function insert(id, index, rootId) {
    var block = createTileBlock(id);
    if (!block) { return; }
    var dispatch = wp.data.dispatch('core/block-editor');
    dispatch.insertBlocks([block], typeof index === 'number' ? index : undefined, rootId || undefined);
    if (dispatch.selectBlock) { dispatch.selectBlock(block.clientId); }
  }

  function mountDragAndDrop() {
    var bound = [];
    function dropTarget(event) {
      return event.target && typeof event.target.closest === 'function' &&
        event.target.closest('.block-editor-block-list__layout, .block-editor-default-block-appender, .editor-styles-wrapper');
    }
    function onDragOver(e) {
      if (!e.dataTransfer || !Array.prototype.includes.call(e.dataTransfer.types || [], 'application/x-novacell-block')) { return; }
      if (!dropTarget(e)) { return; }
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
    }
    function onDrop(e) {
      if (!e.dataTransfer) { return; }
      var id = e.dataTransfer.getData('application/x-novacell-block');
      if (!id || !tiles.some(function (tile) { return tile.id === id; }) || !dropTarget(e)) { return; }
      e.preventDefault();
      e.stopPropagation();
      var node = e.target.closest('[data-block]');
      if (node) {
        var clientId = node.getAttribute('data-block');
        var selected = wp.data.select('core/block-editor');
        var rootId = selected.getBlockRootClientId ? selected.getBlockRootClientId(clientId) : null;
        var index = selected.getBlockIndex ? selected.getBlockIndex(clientId) : undefined;
        insert(id, index, rootId);
      } else {
        insert(id);
      }
    }
    function bind(doc) {
      if (!doc || doc.__novacellBound) { return; }
      doc.__novacellBound = true;
      doc.addEventListener('dragover', onDragOver, false);
      doc.addEventListener('drop', onDrop, false);
      bound.push(doc);
    }
    function scan() {
      bind(document);
      Array.prototype.forEach.call(document.querySelectorAll('iframe'), function (frame) {
        try { if (frame.contentDocument) { bind(frame.contentDocument); } } catch (err) { /* different-origin iframe */ }
      });
    }
    scan();
    var observer = new MutationObserver(scan);
    observer.observe(document.body, { childList: true, subtree: true });
    return function () {
      observer.disconnect();
      bound.forEach(function (doc) {
        doc.removeEventListener('dragover', onDragOver);
        doc.removeEventListener('drop', onDrop);
        delete doc.__novacellBound;
      });
    };
  }

  function TilePalette() {
    useEffect(mountDragAndDrop, []);
    return el('div', { className: 'ncv-tiles' }, tiles.map(function (tile) {
      return el('button', {
        type: 'button', className: 'ncv-tile', key: tile.id,
        draggable: true,
        title: 'Kliknij albo przeciągnij do obszaru strony',
        onClick: function () { insert(tile.id); },
        onDragStart: function (e) {
          e.dataTransfer.setData('application/x-novacell-block', tile.id);
          e.dataTransfer.effectAllowed = 'copy';
        }
      }, el('img', { src: (settings.icons || '') + tile.icon + '.svg', alt: '', width: 25, height: 25 }), el('span', null, tile.label));
    }));
  }

  function colorLuminance(hex) {
    if (!/^#[a-f0-9]{6}$/i.test(hex || '')) return null;
    var rgb = [1, 3, 5].map(function (at) { var x = parseInt(hex.slice(at, at + 2), 16) / 255; return x <= 0.04045 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); });
    return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
  }
  function contrastRatio(a, b) {
    var l1 = colorLuminance(a), l2 = colorLuminance(b);
    if (l1 === null || l2 === null) return null;
    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
  }

  function copyStyle(block, parts, value) {
    var previous = block.attributes.style || {};
    var next = JSON.parse(JSON.stringify(previous));
    var node = next;
    parts.slice(0, -1).forEach(function (key) { node[key] = node[key] || {}; node = node[key]; });
    node[parts[parts.length - 1]] = value;
    wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, { style: next });
  }
  function readStyle(block, parts) {
    var value = block.attributes.style || {};
    parts.forEach(function (key) { value = value && value[key]; });
    return typeof value === 'string' ? value : '';
  }
  function update(block, props) {
    wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, props);
  }

  function TextField(props) {
    return el(components.TextControl, {
      label: props.label, help: props.help || null, value: props.value || '',
      onChange: props.onChange, __nextHasNoMarginBottom: true
    });
  }

  function ElementInspector(props) {
    var block = props.block;
    var tab = props.tab;
    if (!block) {
      return el('p', { className: 'ncv-intro' }, 'Kliknij element na stronie, aby edytować jego treść, wygląd i odstępy.');
    }
    var a = block.attributes;
    var values = [];
    if (tab === 'content') {
      values.push(el('h3', { key: 'h' }, 'Treść zaznaczonego elementu'));
      if (block.name === 'novacell/section') {
        values.push(el(components.SelectControl, {
          key: 'section', label: 'Wybierz sekcję NOVACELL', value: a.sectionId || 'hero',
          options: settings.sections && settings.sections.length ? settings.sections : [{ value: 'hero', label: 'Nagłówek główny' }],
          onChange: function (v) { update(block, { sectionId: v }); }
        }));
        values.push(el(components.SelectControl, {
          key: 'lng', label: 'Język sekcji', value: a.language || 'auto',
          options: [{ value: 'auto', label: 'Automatycznie' }, { value: 'pl', label: 'Język Polski' }, { value: 'en', label: 'English' }],
          onChange: function (v) { update(block, { language: v }); }
        }));
        values.push(el('p', { className: 'ncv-help', key: 's' }, 'Treść tej sekcji pochodzi z NOVACELL Manager. Edytuj ją tam, aby zmiana była widoczna wszędzie.'));
      } else {
        if (Object.prototype.hasOwnProperty.call(a, 'content')) {
          values.push(el('div', { key: 'text' },
            el('p', { className: 'ncv-help' }, 'Edytor WordPress WYSIWYG'),
            el(blockEditor.RichText, {
              tagName: 'div', className: 'ncv-richtext', value: a.content || '',
              onChange: function (v) { update(block, { content: v }); },
              placeholder: 'Wpisz treść elementu...'
            })));
        } else if (Object.prototype.hasOwnProperty.call(a, 'text')) {
          values.push(el(TextField, { key: 'text', label: 'Tekst przycisku', value: a.text || '', onChange: function (v) { update(block, { text: v }); } }));
        }
        if (Object.prototype.hasOwnProperty.call(a, 'level')) {
          values.push(el(components.SelectControl, {
            key: 'level', label: 'Poziom nagłówka', value: String(a.level || 2),
            options: [1, 2, 3, 4, 5, 6].map(function (n) { return { value: String(n), label: 'H' + n }; }),
            onChange: function (v) { update(block, { level: Number(v) }); }
          }));
        }
        if (Object.prototype.hasOwnProperty.call(a, 'url')) {
          values.push(el(TextField, { key: 'url', label: 'Adres linku', value: a.url || '', onChange: function (v) { update(block, { url: v }); } }));
        }
        if (Object.prototype.hasOwnProperty.call(a, 'alt')) {
          values.push(el(TextField, { key: 'alt', label: 'Tekst alternatywny obrazu', value: a.alt || '', onChange: function (v) { update(block, { alt: v }); } }));
        }
        if (values.length === 1) {
          values.push(el('p', { key: 'native', className: 'ncv-help' }, 'Treść tego elementu edytuj bezpośrednio na stronie. Możesz też użyć oryginalnych ustawień Gutenberg.'));
        }
      }
    }
    if (tab === 'appearance') {
      values.push(el('h3', { key: 'h' }, 'Wygląd'));
      values.push(el(components.SelectControl, {
        key: 'align', label: 'Wyrównanie elementu', value: a.align || '',
        options: [ { label: 'Domyślne', value: '' }, { label: 'Szeroki', value: 'wide' }, { label: 'Pełna szerokość', value: 'full' }, { label: 'Lewo', value: 'left' }, { label: 'Środek', value: 'center' }, { label: 'Prawo', value: 'right' } ],
        onChange: function (v) { update(block, { align: v }); }
      }));
      values.push(el('p', { key: 'c0', className: 'ncv-help' }, 'Kolor tekstu'));
      values.push(el(components.ColorPalette, {
        key: 'text-color', value: readStyle(block, ['color', 'text']) || '#183b50', clearable: false,
        colors: [
          { name: 'Granat', color: '#183B50' }, { name: 'Ciemny niebieski', color: '#125077' },
          { name: 'Prawie czarny', color: '#142935' }, { name: 'Biały', color: '#FFFFFF' }
        ], onChange: function (v) { if (v) { copyStyle(block, ['color', 'text'], v); } }
      }));
      values.push(el('p', { key: 'c1', className: 'ncv-help' }, 'Tło elementu'));
      values.push(el(components.ColorPalette, {
        key: 'background', value: readStyle(block, ['color', 'background']) || '#F5FAFC', clearable: false,
        colors: [ { name: 'Jasne tło', color: '#F5FAFC' }, { name: 'Białe', color: '#FFFFFF' }, { name: 'Granat', color: '#183B50' }, { name: 'Błękit', color: '#9ED2DF' } ],
        onChange: function (v) { if (v) { copyStyle(block, ['color', 'background'], v); } }
      }));
      var contrast = contrastRatio(readStyle(block, ['color', 'text']) || '#183B50', readStyle(block, ['color', 'background']) || '#FFFFFF');
      if (contrast !== null && contrast < 4.5) {
        values.push(el('p', { key: 'contrast', className: 'ncv-warning', role: 'status' },
          'Uwaga: kontrast ' + contrast.toFixed(2) + ':1 jest poniżej 4.5:1 dla zwykłego tekstu. Zmień kolor tła lub treści.'));
      }
      values.push(el(TextField, { key: 'font', label: 'Rozmiar tekstu (np. 20px)', value: readStyle(block, ['typography', 'fontSize']), onChange: function (v) { copyStyle(block, ['typography', 'fontSize'], v); } }));
    }
    if (tab === 'dimensions') {
      values.push(el('h3', { key: 'h' }, 'Rozmiary i odstępy'));
      ['padding', 'margin'].forEach(function (group) {
        ['top', 'right', 'bottom', 'left'].forEach(function (side) {
          var labels = { top: 'Góra', right: 'Prawo', bottom: 'Dół', left: 'Lewo' };
          values.push(el(TextField, { key: group + side, label: (group === 'padding' ? 'Odstęp wewnętrzny: ' : 'Margines: ') + labels[side],
            value: readStyle(block, ['spacing', group, side]), help: 'Wpisz np. 16px lub 1rem',
            onChange: function (v) { copyStyle(block, ['spacing', group, side], v); } }));
        });
      });
      values.push(el(TextField, { key: 'radius', label: 'Zaokrąglenie rogów (np. 12px)', value: readStyle(block, ['border', 'radius']), onChange: function (v) { copyStyle(block, ['border', 'radius'], v); } }));
    }
    if (tab === 'advanced') {
      values.push(el('h3', { key: 'h' }, 'Zaawansowane'));
      values.push(el(TextField, { key: 'className', label: 'Dodatkowe klasy CSS', value: a.className || '', onChange: function (v) { update(block, { className: v }); } }));
      values.push(el(TextField, { key: 'anchor', label: 'Kotwica (ID elementu)', value: a.anchor || '', onChange: function (v) { update(block, { anchor: v }); } }));
      values.push(el('p', { key: 'warn', className: 'ncv-help' }, 'Zmiany zapisujesz standardowym przyciskiem Zapisz/Opublikuj w WordPress. Nie tworzymy osobnej kopii treści.'));
    }
    return el('div', { className: 'ncv-inspector' }, values);
  }

  function SidebarContent() {
    var state = useState('content');
    var tab = state[0], setTab = state[1];
    var selected = useSelect(function (select) {
      return select('core/block-editor').getSelectedBlock();
    }, []);
    return el('div', { className: 'ncv-panel' },
      el('p', { className: 'ncv-intro' }, 'Wstaw element lub przeciągnij go na stronę. Wszystko pozostaje standardowymi blokami Gutenberg.'),
      el('h2', null, 'Dodaj element'),
      el(TilePalette, null),
      el('div', { className: 'ncv-tabs', role: 'tablist', 'aria-label': 'Właściwości elementu' },
        [ ['content', 'Treść'], ['appearance', 'Wygląd'], ['dimensions', 'Rozmiary'], ['advanced', 'Zaawansowane'] ].map(function (item, i, all) {
          return el('button', {
            id: 'ncv-tab-' + item[0],
            'aria-controls': 'ncv-properties-' + item[0],
            key: item[0], type: 'button', role: 'tab', 'aria-selected': tab === item[0], tabIndex: tab === item[0] ? 0 : -1,
            onClick: function () { setTab(item[0]); },
            onKeyDown: function (e) {
              var next = -1;
              if (e.key === 'ArrowRight') next = (i + 1) % all.length;
              if (e.key === 'ArrowLeft') next = (i - 1 + all.length) % all.length;
              if (e.key === 'Home') next = 0;
              if (e.key === 'End') next = all.length - 1;
              if (next !== -1) {
                e.preventDefault();
                setTab(all[next][0]);
                var target = document.getElementById('ncv-tab-' + all[next][0]);
                if (target) target.focus();
              }
            }
          }, item[1]);
        })),
      el('div', { role: 'tabpanel', id: 'ncv-properties-' + tab, 'aria-labelledby': 'ncv-tab-' + tab }, el(ElementInspector, { block: selected, tab: tab })),
      el('p', { className: 'ncv-help' }, 'Edycja strony głównej NOVACELL: NOVACELL Manager → Strona główna i sekcje.')
    );
  }

  wp.blocks.registerBlockType('novacell/section', {
    edit: function (props) {
      var opts = settings.sections || [];
      var selected = opts.filter(function (x) { return x.value === props.attributes.sectionId; })[0];
      return el('div', wp.blockEditor.useBlockProps({ className: 'ncv-section-preview' }),
        el('strong', null, selected ? selected.label : 'Sekcja NOVACELL'),
        el('p', null, 'Podgląd sekcji dynamicznej. Treść pochodzi z NOVACELL Manager i aktualizuje się automatycznie.'),
        el(components.SelectControl, { label: 'Sekcja', value: props.attributes.sectionId || 'hero', options: opts.length ? opts : [ { value: 'hero', label: 'Nagłówek główny' } ],
          onChange: function (v) { props.setAttributes({ sectionId: v }); } })
      );
    },
    save: function () { return null; }
  });
  var MoreMenuItem = (wp.editor && wp.editor.PluginSidebarMoreMenuItem) || (wp.editPost && wp.editPost.PluginSidebarMoreMenuItem);
  function VisualPlugin() {
    return el(wp.element.Fragment, null,
      MoreMenuItem ? el(MoreMenuItem, { target: 'novacell-visual', icon: 'layout' }, 'Otwórz NOVACELL Visual Editor') : null,
      el(Sidebar, { name: 'novacell-visual', title: 'NOVACELL Visual Editor', icon: 'layout' }, el(SidebarContent))
    );
  }
  // When launched from the visible NOVACELL menu, reveal the Gutenberg sidebar.
  // The normal editor still has the standard manual Plugin sidebar control.
  if (settings.autoOpen) {
    var retry = 0;
    var openNovaCellSidebar = function () {
      try {
        var editPost = wp.data.dispatch('core/edit-post');
        if (editPost && typeof editPost.openGeneralSidebar === 'function') {
          editPost.openGeneralSidebar('novacell-visual-editor/novacell-visual');
          return;
        }
      } catch (error) { /* Older editor variant: use the native sidebar menu. */ }
      if (++retry < 10) { window.setTimeout(openNovaCellSidebar, 400); }
    };
    window.setTimeout(openNovaCellSidebar, 300);
  }
  wp.plugins.registerPlugin('novacell-visual-editor', {
    icon: 'layout', render: VisualPlugin
  });
})(window.wp);
