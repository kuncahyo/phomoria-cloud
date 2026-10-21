@extends('layouts.admin')

@section('title', 'Edit Layout Frame - Phomoria Cloud')

@section('styles')
<style>
.editor-page{padding:34px 0 80px}.eyebrow{color:#888890;font-size:10px;font-weight:750;letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px}.heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:22px}.heading h1{margin:0 0 7px;font-size:32px;letter-spacing:-.8px}.sub{margin:0;color:#92929b;font-size:13px;line-height:1.6}.back{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 15px;border:1px solid rgba(255,255,255,.14);border-radius:999px;color:#ddd;font-size:12px}.panel{padding:20px;border:1px solid rgba(255,255,255,.09);border-radius:17px;background:rgba(255,255,255,.025);margin-bottom:16px}.panel-title{margin:0 0 15px;font-size:15px}.field-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.field label,.placement-fields label{display:block;margin-bottom:6px;color:#a5a5ae;font-size:11px;font-weight:650}.field input,.field select,.placement-fields input{width:100%;min-height:39px;padding:0 10px;border:1px solid rgba(255,255,255,.13);border-radius:8px;background:#151518;color:#fff;font:inherit;font-size:13px}.help{margin-top:7px;color:#777780;font-size:11px;line-height:1.5}.workspace{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,1fr);gap:16px;align-items:start}.canvas-panel{min-width:0}.canvas-wrap{position:relative;width:100%;overflow:hidden;border:1px solid rgba(255,255,255,.14);border-radius:10px;background-color:#19191d;background-image:linear-gradient(45deg,#25252a 25%,transparent 25%),linear-gradient(-45deg,#25252a 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#25252a 75%),linear-gradient(-45deg,transparent 75%,#25252a 75%);background-size:18px 18px;background-position:0 0,0 9px,9px -9px,-9px 0;touch-action:pan-y}.canvas-image{display:block;width:100%;height:auto;user-select:none;-webkit-user-drag:none;pointer-events:none}.placement-layer{position:absolute;inset:0}.placement-box{position:absolute;border:2px solid #55e28c;background:rgba(47,205,116,.17);box-shadow:0 0 0 1px rgba(0,0,0,.35);cursor:move;touch-action:none;transform-origin:center center}.placement-box.selected{border-color:#ffe27a;background:rgba(255,205,65,.22);z-index:5}.placement-tag{position:absolute;left:3px;top:3px;padding:2px 5px;border-radius:4px;background:rgba(0,0,0,.78);color:#fff;font-size:10px;font-weight:750;white-space:nowrap;pointer-events:none}.resize-handle{position:absolute;right:-6px;bottom:-6px;width:13px;height:13px;border:2px solid #111;border-radius:3px;background:#fff;cursor:nwse-resize;touch-action:none}.canvas-hint{margin-top:10px;color:#888891;font-size:11px;line-height:1.5}.summary{margin-top:12px;padding:12px;border:1px solid rgba(255,255,255,.08);border-radius:10px;background:rgba(255,255,255,.025);color:#d6d6dc;font-size:12px}.summary strong{color:#fff}.placement-list{display:flex;flex-direction:column;gap:11px}.placement-card{padding:13px;border:1px solid rgba(255,255,255,.09);border-radius:12px;background:rgba(0,0,0,.13)}.placement-card.active{border-color:rgba(255,226,122,.55);background:rgba(255,226,122,.045)}.placement-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}.placement-select{border:0;background:transparent;color:#fff;font-size:12px;font-weight:750;cursor:pointer}.remove-placement{border:1px solid rgba(255,100,100,.25);border-radius:7px;background:transparent;color:#ffaaaa;padding:5px 8px;font-size:10px;cursor:pointer}.placement-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.placement-fields .source-field{grid-column:span 2}.source-field input{border-color:rgba(85,226,140,.35)}.toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}.toolbar p{margin:0;color:#85858e;font-size:11px}.add-button,.save-button{min-height:40px;padding:0 15px;border:0;border-radius:999px;background:#fff;color:#111;font-size:12px;font-weight:750;cursor:pointer}.save-row{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:18px}.save-note{color:#777780;font-size:11px;line-height:1.5}.error-box{margin-bottom:15px;padding:13px;border:1px solid rgba(255,90,90,.25);border-radius:10px;background:rgba(255,70,70,.07);color:#ffb7b7;font-size:12px}.profile-hint{margin-top:8px;color:#85858e;font-size:11px}@media(max-width:900px){.workspace{grid-template-columns:1fr}.field-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.heading{display:block}.back{margin-top:15px}.field-grid{grid-template-columns:1fr}.placement-fields{grid-template-columns:repeat(2,minmax(0,1fr))}.placement-fields .source-field{grid-column:span 2}.save-row{align-items:flex-start;flex-direction:column}.save-button{width:100%}}
</style>
@endsection

@section('content')
<div class="editor-page">
    <div class="heading">
        <div>
            <div class="eyebrow">Frame Layout Editor</div>
            <h1>{{ $frame->name }}</h1>
            <p class="sub">Atur setiap lubang foto pada gambar frame. Posisi disimpan dalam koordinat asli PNG ({{ $frame->width }} × {{ $frame->height }} px).</p>
        </div>
        <a class="back" href="{{ route('admin.frames.index', $isSuperAdmin ? ['user_id' => $targetUser->id] : []) }}">← Kembali ke Frames</a>
    </div>

    @if ($errors->any())
        <div class="error-box">
            <strong>Periksa kembali data editor:</strong>
            <ul style="margin:7px 0 0;padding-left:18px">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form id="frameEditorForm" method="POST" action="{{ route('admin.frames.update', array_merge(['frame' => $frame->id], $isSuperAdmin ? ['user_id' => $targetUser->id] : [])) }}">
        @csrf
        @method('PUT')

        @if ($isSuperAdmin)
            <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
        @endif

        <section class="panel">
            <h2 class="panel-title">Pengaturan Frame</h2>
            <div class="field-grid">
                <div class="field">
                    <label for="category">Kategori Frame</label>
                    <select id="category" name="category" required>
                        <option value="standar" @selected(old('category', $frame->category) === 'standar')>Standar</option>
                        <option value="split" @selected(old('category', $frame->category) === 'split')>Split</option>
                    </select>
                    <div class="profile-hint">Tentukan kategori frame yang akan digunakan.</div>
                </div>
                <div class="field">
                    <label for="printer_profile">Printer Profile</label>
                    <select id="printer_profile" name="printer_profile" required>
                        <option value="PROFILE_1" @selected(old('printer_profile', $frame->printer_profile ?? 'PROFILE_1') === 'PROFILE_1')>Profile 1</option>
                        <option value="PROFILE_2" @selected(old('printer_profile', $frame->printer_profile ?? 'PROFILE_1') === 'PROFILE_2')>Profile 2</option>
                    </select>
                    <div class="profile-hint">Profil printer yang akan dikaitkan dengan frame ini.</div>
                </div>
                <div class="field">
                    <label>Jumlah lubang fisik</label>
                    <input id="physicalCount" type="text" value="{{ $frame->placements->count() }}" readonly>
                </div>
                <div class="field">
                    <label>Jumlah sumber foto unik</label>
                    <input id="sourceCount" type="text" value="-" readonly>
                </div>
            </div>
        </section>

        <div class="workspace">
            <section class="panel canvas-panel">
                <div class="toolbar">
                    <h2 class="panel-title" style="margin:0">Preview & Posisi Lubang</h2>
                    <button type="button" class="add-button" id="addPlacement">+ Tambah Lubang</button>
                </div>
                <div class="canvas-wrap" id="canvasWrap" style="aspect-ratio: {{ max(1, (int) $frame->width) }} / {{ max(1, (int) $frame->height) }}">
                    <img class="canvas-image" id="frameImage" src="{{ asset('storage/' . $frame->image_path) }}" alt="{{ $frame->name }}">
                    <div class="placement-layer" id="placementLayer"></div>
                </div>
                <div class="canvas-hint">Klik kotak untuk memilih. Seret bagian tengah untuk memindahkan, atau tarik handle sudut kanan bawah untuk mengubah ukuran. Rotasi dapat diatur dari kolom angka.</div>
                <div class="summary" id="mappingSummary"></div>
            </section>

            <section class="panel">
                <div class="toolbar">
                    <div>
                        <h2 class="panel-title" style="margin:0 0 4px">Daftar Lubang</h2>
                        <p>Slot = lubang fisik · Source = foto yang diambil</p>
                    </div>
                </div>
                <div class="placement-list" id="placementList"></div>
                <div class="help" style="margin-top:13px">Untuk membuat duplikat, gunakan nomor Source yang sama pada beberapa lubang. Contoh enam lubang: Source 1, 2, 3, 1, 2, 3 berarti hanya tiga foto yang diambil.</div>
            </section>
        </div>

        <div class="save-row">
            <div class="save-note">Frame lama dengan source_slot kosong dimulai dari pemetaan satu lubang = satu sumber. Sesuaikan sumber yang berulang sebelum menyimpan jika frame tersebut memakai duplikat.</div>
            <button type="submit" class="save-button">Simpan Layout Frame</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const imageWidth = {{ max(1, (int) $frame->width) }};
    const imageHeight = {{ max(1, (int) $frame->height) }};
    const initial = @json($initialPlacements);

    const layer = document.getElementById('placementLayer');
    const list = document.getElementById('placementList');
    const canvas = document.getElementById('canvasWrap');
    const sourceCount = document.getElementById('sourceCount');
    const physicalCount = document.getElementById('physicalCount');
    const summary = document.getElementById('mappingSummary');
    const form = document.getElementById('frameEditorForm');

    let placements = initial.map((item, index) => ({...item, key: index + 1}));
    let selectedKey = placements.length ? placements[0].key : null;
    let nextKey = placements.length + 1;
    let dragState = null;

    const byKey = key => placements.find(item => item.key === key);
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    function updateCounts() {
        const unique = new Set(placements.map(p => Number(p.source_slot)).filter(n => Number.isInteger(n) && n > 0));
        physicalCount.value = placements.length;
        sourceCount.value = unique.size;
        summary.innerHTML = '<strong>' + placements.length + '</strong> lubang fisik menggunakan <strong>' + unique.size + '</strong> sumber foto unik. Jumlah pengambilan foto nantinya mengikuti jumlah sumber unik.';
    }

    function updateGeometry(key) {
        const p = byKey(key);
        const box = layer.querySelector('[data-key="' + key + '"]');
        if (!p || !box) return;
        box.style.left = (p.x / imageWidth * 100) + '%';
        box.style.top = (p.y / imageHeight * 100) + '%';
        box.style.width = (p.width / imageWidth * 100) + '%';
        box.style.height = (p.height / imageHeight * 100) + '%';
        box.style.transform = 'rotate(' + p.rotation + 'deg)';
        box.classList.toggle('selected', selectedKey === key);
        const tag = box.querySelector('.placement-tag');
        tag.textContent = 'Lubang ' + p.slot + ' / Foto ' + p.source_slot;
    }

    function createField(parent, labelText, field, value, min, step) {
        const wrap = document.createElement('div');
        if (field === 'source_slot') wrap.className = 'source-field';
        const label = document.createElement('label');
        label.textContent = labelText;
        const input = document.createElement('input');
        input.type = 'number';
        input.name = 'placements[' + field.index + '][' + field.name + ']';
        input.value = value;
        input.min = min;
        input.step = step || '1';
        input.required = true;
        input.addEventListener('input', () => {
            const p = byKey(field.key);
            if (!p) return;
            p[field.name] = field.name === 'rotation' ? Number(input.value) : parseInt(input.value || '0', 10);
            if (field.name === 'slot' || field.name === 'source_slot') {
                const box = layer.querySelector('[data-key="' + field.key + '"]');
                if (box) box.querySelector('.placement-tag').textContent = 'Lubang ' + p.slot + ' / Foto ' + p.source_slot;
                updateCounts();
            }
            if (['x','y','width','height','rotation'].includes(field.name)) updateGeometry(field.key);
        });
        wrap.append(label, input);
        parent.appendChild(wrap);
    }

    function render() {
        layer.innerHTML = '';
        list.innerHTML = '';

        placements.forEach((p, index) => {
            const box = document.createElement('div');
            box.className = 'placement-box';
            box.dataset.key = p.key;
            const tag = document.createElement('span');
            tag.className = 'placement-tag';
            const handle = document.createElement('span');
            handle.className = 'resize-handle';
            box.append(tag, handle);
            box.addEventListener('pointerdown', event => {
                if (event.target === handle) return;
                select(p.key);
                startDrag(event, p.key, 'move');
            });
            handle.addEventListener('pointerdown', event => {
                event.stopPropagation();
                select(p.key);
                startDrag(event, p.key, 'resize');
            });
            layer.appendChild(box);
            updateGeometry(p.key);

            const card = document.createElement('div');
            card.className = 'placement-card' + (selectedKey === p.key ? ' active' : '');
            card.dataset.key = p.key;
            const head = document.createElement('div');
            head.className = 'placement-head';
            const choose = document.createElement('button');
            choose.type = 'button';
            choose.className = 'placement-select';
            choose.textContent = 'Lubang fisik #' + p.slot;
            choose.addEventListener('click', () => select(p.key));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-placement';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', () => {
                if (placements.length <= 1) {
                    alert('Minimal harus ada satu lubang foto.');
                    return;
                }
                placements = placements.filter(item => item.key !== p.key);
                if (selectedKey === p.key) selectedKey = placements[0].key;
                render();
            });
            head.append(choose, remove);
            const fields = document.createElement('div');
            fields.className = 'placement-fields';
            const fieldDefs = [
                ['Nomor slot fisik','slot',p.slot,1,'1'],
                ['Source foto','source_slot',p.source_slot,1,'1'],
                ['X (px)','x',p.x,0,'1'],
                ['Y (px)','y',p.y,0,'1'],
                ['Lebar (px)','width',p.width,1,'1'],
                ['Tinggi (px)','height',p.height,1,'1'],
                ['Rotasi (°)','rotation',p.rotation,-360,'0.1']
            ];
            fieldDefs.forEach(([label,name,value,min,step]) => {
                createField(fields, label, {
                    index, key:p.key, name
                }, value, min, step);
            });
            card.append(head, fields);
            list.appendChild(card);
        });
        updateCounts();
    }

    function select(key) {
        selectedKey = key;
        layer.querySelectorAll('.placement-box').forEach(box => {
            box.classList.toggle('selected', Number(box.dataset.key) === key);
        });
        list.querySelectorAll('.placement-card').forEach(card => {
            card.classList.toggle('active', Number(card.dataset.key) === key);
        });
    }

    function startDrag(event, key, mode) {
        event.preventDefault();
        const p = byKey(key);
        if (!p) return;
        dragState = {
            key, mode,
            startX: event.clientX,
            startY: event.clientY,
            x: p.x, y: p.y, width: p.width, height: p.height
        };
        try { event.currentTarget.setPointerCapture(event.pointerId); } catch (_) {}
    }

    function pointerMove(event) {
        if (!dragState) return;
        const p = byKey(dragState.key);
        if (!p) return;
        const rect = canvas.getBoundingClientRect();
        const dx = (event.clientX - dragState.startX) / rect.width * imageWidth;
        const dy = (event.clientY - dragState.startY) / rect.height * imageHeight;

        if (dragState.mode === 'move') {
            p.x = Math.round(clamp(dragState.x + dx, 0, imageWidth - p.width));
            p.y = Math.round(clamp(dragState.y + dy, 0, imageHeight - p.height));
        } else {
            p.width = Math.round(clamp(dragState.width + dx, 1, imageWidth - p.x));
            p.height = Math.round(clamp(dragState.height + dy, 1, imageHeight - p.y));
        }
        updateGeometry(p.key);
        updateFieldValues(p.key);
    }

    function updateFieldValues(key) {
        const index = placements.findIndex(p => p.key === key);
        const p = byKey(key);
        const card = list.children[index];
        if (!card || !p) return;
        ['x','y','width','height','rotation'].forEach(name => {
            const input = card.querySelector('input[name="placements[' + index + '][' + name + ']"]');
            if (input) input.value = p[name];
        });
    }

    function pointerUp() { dragState = null; }
    canvas.addEventListener('pointermove', pointerMove);
    window.addEventListener('pointerup', pointerUp);
    window.addEventListener('pointercancel', pointerUp);

    document.getElementById('addPlacement').addEventListener('click', () => {
        const maxSlot = placements.reduce((m,p) => Math.max(m, Number(p.slot)||0), 0);
        const maxSource = placements.reduce((m,p) => Math.max(m, Number(p.source_slot)||0), 0);
        const w = Math.max(1, Math.round(imageWidth * .22));
        const h = Math.max(1, Math.round(imageHeight * .22));
        const p = {
            key: nextKey++,
            slot: maxSlot + 1,
            source_slot: maxSource + 1,
            x: Math.round((imageWidth - w) / 2),
            y: Math.round((imageHeight - h) / 2),
            width: w,
            height: h,
            rotation: 0
        };
        placements.push(p);
        selectedKey = p.key;
        render();
    });

    form.addEventListener('submit', event => {
        const seen = new Set();
        for (const p of placements) {
            const slot = Number(p.slot);
            const source = Number(p.source_slot);
            if (!Number.isInteger(slot) || slot < 1 || seen.has(slot)) {
                event.preventDefault();
                alert('Nomor slot fisik harus berupa angka positif dan tidak boleh duplikat.');
                return;
            }
            seen.add(slot);
            if (!Number.isInteger(source) || source < 1) {
                event.preventDefault();
                alert('Source foto harus berupa angka positif.');
                return;
            }
            if (p.x < 0 || p.y < 0 || p.width < 1 || p.height < 1 ||
                p.x + p.width > imageWidth || p.y + p.height > imageHeight) {
                event.preventDefault();
                alert('Ada lubang foto yang berada di luar batas gambar frame.');
                return;
            }
        }
    });

    render();
})();
</script>
@endsection
