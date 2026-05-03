# Setup en un equipo nuevo (laptop / segundo PC)

Guía para replicar el entorno de desarrollo del proyecto **Media-Folders-APP** en un equipo adicional. Sigue los pasos en orden.

---

## 1. Pre-requisitos a instalar

| Software | Para qué | Cómo |
|---|---|---|
| **Git** | clonar y versionar | <https://git-scm.com/download/win> |
| **Node.js LTS** | desarrollar la PWA Vue | <https://nodejs.org/es/download> (instalador `.msi`) |
| **VS Code** | editor | <https://code.visualstudio.com> |
| **Dropbox** | sincronización de chats Claude Code y backups | <https://www.dropbox.com/install> — instala y deja que sincronice **completamente** la carpeta `Dropbox/.claude/` antes de seguir |
| **Claude Code** | el CLI / extensión | <https://docs.claude.com/claude-code> |

Comprobaciones tras instalar (PowerShell nuevo, no reutilizar):

```powershell
git --version          # debe responder
node -v                # >= v20
npm -v                 # >= v10
```

---

## 2. Permitir ejecución de scripts PowerShell

Necesario porque `npm`, `deploy.ps1` y los scripts del proyecto son `.ps1`.

```powershell
# PowerShell como administrador (clic derecho → "Ejecutar como administrador")
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
# Pulsa "S" cuando pregunte
```

Cierra y abre una PowerShell nueva (sin admin) para los siguientes pasos.

---

## 3. Clonar el repo

Usa **la misma ruta** que en el equipo principal — así Claude Code reutiliza tus sesiones sincronizadas via Dropbox sin reinventar carpetas:

```powershell
# Asegúrate de que F:\dev\proyectos\ existe (ajusta a tu unidad)
mkdir -Force F:\dev\proyectos
cd F:\dev\proyectos
git clone https://github.com/Kyleon/Media-Folders-APP.git
cd Media-Folders-APP
```

> **Si tu portátil no tiene unidad `F:`** y no quieres pelearte con letras: usa `subst F: D:\` (donde `D:\` es donde tengas tus proyectos) para crear una letra virtual cada arranque. O cambia la letra de la unidad de datos en Administración de discos.

---

## 4. Sincronizar la carpeta de Claude Code via Dropbox

Para que aparezcan **todas tus sesiones de chat** del equipo principal en el portátil:

```powershell
# Espera a que Dropbox haya bajado completamente la carpeta:
ls C:\Users\<tu-usuario>\Dropbox\.claude   # debe mostrar projects, sessions, etc.

# Si ya existe ~/.claude/ local, renómbrala (por si acaso):
Move-Item "$env:USERPROFILE\.claude" "$env:USERPROFILE\.claude.backup-$(Get-Date -Format yyyyMMdd)"

# Crear el junction
cmd /c mklink /J "$env:USERPROFILE\.claude" "$env:USERPROFILE\Dropbox\.claude"

# Verificar
Get-Item "$env:USERPROFILE\.claude" | Select Name, LinkType, Target
# Debe mostrar LinkType: Junction
```

A partir de aquí, abrir Claude Code en el directorio del proyecto te dará acceso a todo el historial:

```powershell
cd F:\dev\proyectos\Media-Folders-APP
claude
# dentro: /resume — verás todas las sesiones sincronizadas
```

⚠️ **No uses Claude Code en los dos equipos a la vez** — Dropbox crearía conflictos en los `.jsonl`. Cierra siempre uno antes de abrir el otro.

---

## 5. Instalar dependencias de la PWA

```powershell
cd F:\dev\proyectos\Media-Folders-APP\app
npm install
```

Tarda 1-2 minutos. Crea `app/node_modules/` (excluida de Git, ~250 MB).

Verifica que arranca:

```powershell
npm run dev
# debe servir en http://localhost:5173/
```

(Ctrl+C para parar.)

---

## 6. Configurar SFTP para deploy a Hostinger

**Este paso es por equipo** — el archivo `sftp.json` no se sincroniza por seguridad (contiene la contraseña FTP).

Crea `F:\dev\proyectos\Media-Folders-APP\app\.vscode\sftp.json`:

```json
{
  "name": "YPVA App",
  "host": "145.14.152.131",
  "protocol": "ftp",
  "port": 21,
  "username": "u604760889",
  "password": "TU_PASSWORD_FTP",
  "remotePath": "/domains/yezraelperez.es/public_html/app/",
  "context": "dist",
  "uploadOnSave": false,
  "ignore": [
    ".vscode", ".git", "node_modules", "src", "public",
    "package.json", "package-lock.json", "vite.config.js",
    "index.html", "build.ps1", "README.md", ".gitignore", ".DS_Store"
  ]
}
```

Reemplaza `TU_PASSWORD_FTP` por la contraseña real (la tienes en el equipo principal).

A partir de ahí, deploy a producción:

```powershell
cd F:\dev\proyectos\Media-Folders-APP\app
.\deploy.ps1
```

---

## 7. (Opcional) WordPress local en el portátil

Si quieres también probar el plugin contra un WordPress en el portátil (no solo contra producción):

1. Instala **Local by Flywheel**, **XAMPP** o **Laragon**.
2. Crea un sitio nuevo con dominio local (ej: `yezraelperez.test`).
3. Importa la BD desde producción si quieres datos reales (UpdraftPlus o similar).
4. Crea junction del plugin:
   ```powershell
   $wp = "C:\ruta\a\tu\wp-local\wp-content\plugins"
   cd $wp
   cmd /c mklink /J yz-media-folders "F:\dev\proyectos\Media-Folders-APP\plugin"
   ```
5. Activa el plugin en `wp-admin → Plugins`.

Si **NO** vas a tener WP local en el portátil, tampoco pasa nada — la PWA en `npm run dev` apuntará a tu URL de producción y trabajarás contra ella.

---

## 8. (Opcional) Settings Sync de VS Code

Para tener las mismas extensiones, atajos, settings, theme, etc:

1. En VS Code: ⚙️ → **Turn on Settings Sync**.
2. Inicia sesión con tu cuenta de GitHub o Microsoft (la misma que en el equipo principal).
3. Selecciona qué sincronizar (Settings, Keybindings, Extensions, UI State, Snippets, Tasks).

---

## 9. Comprobación final

```powershell
cd F:\dev\proyectos\Media-Folders-APP

# Repo en orden
git status                  # working tree clean
git log --oneline -5        # ves los commits

# PWA arranca
cd app
npm run dev                 # http://localhost:5173 OK

# Deploy funciona (no lo lances en producción si no tienes nada que subir)
# .\deploy.ps1 -SkipBuild
```

✅ Listo. Ya puedes desarrollar desde este equipo igual que desde el principal.

---

## Flujo diario entre equipos

Cada vez que cambies de equipo:

```powershell
# Antes de empezar a trabajar
cd F:\dev\proyectos\Media-Folders-APP
git pull

# Trabaja normalmente...

# Antes de irte al otro equipo
git add .
git commit -m "..."
git push
```

Y como `.claude/` está en Dropbox, las sesiones de chat aparecen automáticamente. Solo tienes que:

```powershell
claude
# /resume — selecciona la sesión donde te quedaste
```

---

## Problemas frecuentes

### `npm install` tarda muchísimo o falla

Es normal en la primera ejecución. Si falla:
```powershell
npm cache clean --force
Remove-Item -Recurse -Force node_modules, package-lock.json
npm install
```

### Claude Code no ve mis sesiones en el portátil

- Verifica que Dropbox haya bajado completamente la carpeta `.claude` (no en modo "online only").
- Comprueba que el junction está bien: `Get-Item ~/.claude | Select LinkType, Target` debe mostrar `Junction`.
- Asegúrate de abrir Claude Code en **la misma ruta absoluta** del proyecto (`F:\dev\proyectos\Media-Folders-APP`).

### Conflictos de Dropbox en `.claude/`

Si te aparece `archivo (Conflicto de Yezrael).jsonl`:
- Significa que tuviste Claude Code abierto en los dos equipos a la vez.
- Mira las dos versiones, decide cuál quedarte, borra la otra.

### `git pull` falla con conflictos

Probablemente has commiteado en los dos equipos sin sincronizar. Resolución típica:
```powershell
git stash
git pull --rebase
git stash pop
# Resuelve conflictos en VS Code, git add, git rebase --continue
```
