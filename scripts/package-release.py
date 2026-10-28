from pathlib import Path
import zipfile
root=Path(__file__).resolve().parents[1]
out=root/'dist';out.mkdir(exist_ok=True)
files=['index.php','admin.php','instalar.php','mips-preview.php','.htaccess','config.example.php','database.sql','README.md','scripts/setup.php','data/catalog.json','data/sample.json','data/mips-ready.json','data/mips-pending.json','data/.htaccess','storage/.htaccess','images/Design sem nome (26).png']
files += [str(p.relative_to(root)).replace('\\','/') for folder in ['app','assets'] for p in (root/folder).rglob('*') if p.is_file()]
target=out/'eifarma-cpanel.zip'
with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED) as z:
 for name in files:z.write(root/name,name)
with zipfile.ZipFile(target) as z:
 assert 'config.local.php' not in z.namelist()
 assert not any(n.endswith('.xlsx') or 'acesso-local' in n for n in z.namelist())
 assert len(z.namelist())==len(files)
print(f'{target}: {len(files)} arquivos; {target.stat().st_size} bytes')
