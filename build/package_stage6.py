from pathlib import Path
import hashlib, zipfile, json, shutil
root=Path('/mnt/data/WP-NovaCell-stage6')
dist=root/'dist';dist.mkdir(exist_ok=True)
plugins={
    'NOVACELL-Manager-0.6.0.zip':root/'plugins/novacell-manager',
    'NOVACELL-Theme-0.5.0.zip':root/'themes/novacell',
    'NOVACELL-Visual-Editor-0.2.0.zip':root/'plugins/novacell-visual-editor',
}
all_items={}
for name,src in plugins.items():
    dst=dist/name
    with zipfile.ZipFile(dst,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for p in sorted(src.rglob('*')):
            if p.is_file() and p.name not in {'.DS_Store','README.md'} and not p.name.endswith(('~','.swp')):
                z.write(p,arcname=src.name+'/'+p.relative_to(src).as_posix())
    with zipfile.ZipFile(dst) as z:
        assert z.testzip() is None
        names=z.namelist()
        assert names and all(n.startswith(src.name+'/') for n in names)
    all_items[name]={'sha256':hashlib.sha256(dst.read_bytes()).hexdigest(),'size':dst.stat().st_size,'count':len(names)}
with zipfile.ZipFile(dist/'NOVACELL-Etap-6-Instalatory.zip','w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for name in plugins:
        z.write(dist/name,arcname=name)
    z.write(root/'docs/ETAP6-INSTRUKCJA.md',arcname='Instrukcja-ETAP6.md')
    z.write(root/'releases/ETAP6_2026-11-07.md',arcname='Historia-wersji-ETAP6.md')
(dist/'SHA256SUMS.txt').write_text(''.join(f"{data['sha256']}  {name}\n" for name,data in all_items.items()))
(dist/'manifest.json').write_text(json.dumps(all_items,indent=2,ensure_ascii=False))
print(json.dumps(all_items,indent=2,ensure_ascii=False))
