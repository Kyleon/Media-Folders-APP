# Setup en un equipo nuevo (laptop / segundo PC)

Guía para replicar el entorno de desarrollo del proyecto **Media-Folders-APP** en un equipo adicional. Sigue los pasos en orden.

---

## Rutas por equipo

El proyecto se desarrolla en 3 equipos. Cada uno usa una unidad distinta — los ejemplos de esta guía usan `L:` por ser el equipo intermedio, pero **sustituye la letra por la que corresponda a tu equipo**:

| Equipo | Ruta del proyecto |
|---|---|
| Equipo C | `C:\dev\Media-Folders-APP` |
| Equipo L (este) | `L:\dev\Media-Folders-APP` |
| Equipo F | `F:\dev\Media-Folders-APP` |

> Importante: la carpeta de Claude Code en `~/.claude/projects/` se nombra a partir de la ruta absoluta del proyecto (ej. `l--dev-Media-Folders-APP`), así que **cada equipo tiene su propia carpeta de sesiones**. La sincronización via Dropbox de `~/.claude/` mantiene visibles todas las sesiones de los 3 equipos a la vez (cada una bajo su carpeta de origen).

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

## 3. Clonar el repo y arrancar el setup

Cada equipo usa su propia letra de unidad (ver tabla "Rutas por equipo" arriba). El ejemplo de abajo usa `L:` — sustitúyela por la tuya (`C:` o `F:`):

```powershell
# Asegúrate de que <UNIDAD>:\dev\ existe (sustituye la letra)
mkdir -Force L:\dev
cd L:\dev

# Clonar (pide credenciales de GitHub la primera vez si el repo es privado)
git clone https://github.com/Kyleon/Media-Folders-APP.git

# Ejecutar el bootstrap automático
cd Media-Folders-APP\scripts
.\setup-laptop.ps1
```

> El script detecta automáticamente si ya estás dentro del repo y, en lugar de re-clonar, hace `git pull`. Después se encarga de `npm install`, plantilla de `sftp.json` y junction a Dropbox.

### Autenticarse con GitHub la primera vez

`git clone` con HTTPS pedirá tus credenciales:

- **Más fácil**: instala [Git Credential Manager](https://github.com/git-ecosystem/git-credential-manager) (suele venir con Git for Windows). Te abrirá el navegador para login con tu cuenta de GitHub. Una sola vez.
- **Alternativa**: usa un [Personal Access Token](https://github.com/settings/tokens) con scope `repo`. Cuando Git te pida la password, pega el token.

> **Si el equipo no tiene la letra de unidad esperada** y no quieres pelearte con letras: usa `subst L: D:\` (sustituye `L:` por la letra que necesites y `D:\` por donde tengas tus proyectos) para crear una letra virtual cada arranque. O cambia la letra de la unidad de datos en Administración de discos.

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
cd L:\dev\Media-Folders-APP
claude
# dentro: /resume — verás todas las sesiones sincronizadas
```

⚠️ **No uses Claude Code en los dos equipos a la vez** — Dropbox crearía conflictos en los `.jsonl`. Cierra siempre uno antes de abrir el otro.

---

## 5. Instalar dependencias de la PWA

```powershell
cd L:\dev\Media-Folders-APP\app
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

Crea `L:\dev\Media-Folders-APP\app\.vscode\sftp.json`:

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
cd L:\dev\Media-Folders-APP\app
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
   cmd /c mklink /J yz-media-folders "L:\dev\Media-Folders-APP\plugin"
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
cd L:\dev\Media-Folders-APP

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
cd L:\dev\Media-Folders-APP
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
- Asegúrate de abrir Claude Code en la **ruta absoluta del proyecto en este equipo** (la que corresponda según la tabla "Rutas por equipo": `C:\dev\Media-Folders-APP`, `L:\dev\Media-Folders-APP` o `F:\dev\Media-Folders-APP`). Cada equipo tiene su propia carpeta de sesiones bajo `~/.claude/projects/`, todas visibles via Dropbox.

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
