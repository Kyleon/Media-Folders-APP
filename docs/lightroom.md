# Sincronización con Adobe Lightroom Classic

El plugin `yz-media-folders` expone endpoints REST diseñados para que Lightroom
Classic publique fotos directamente al WordPress.

## Endpoints

Base: `https://yezraelperez.es/wp-json/yzmf/v1/lightroom/`

| Método | Ruta         | Función                                          |
|--------|--------------|--------------------------------------------------|
| GET    | `/check`     | Healthcheck (devuelve user, version, site)       |
| GET    | `/folders`   | Lista de carpetas YZMF disponibles               |
| POST   | `/publish`   | Sube una foto a una carpeta                      |

Auth: **Application Password** (Basic Auth `Authorization: Basic base64(user:app_pwd)`).

### Body de `/publish` (multipart/form-data)

| Campo        | Tipo   | Notas                                                 |
|--------------|--------|-------------------------------------------------------|
| `file`       | File   | Imagen exportada por Lightroom                        |
| `folder_id`  | int    | ID de carpeta YZMF destino (0 = sin carpeta)          |
| `title`      | string | Título del attachment                                 |
| `caption`    | string | Pie de foto                                           |
| `alt`        | string | Alt text                                              |
| `description`| string | Descripción larga                                     |
| `remote_id`  | int    | (Opcional) ID del attachment previo si republicamos   |

Respuesta:
```json
{
  "id": 1234,
  "remote_id": 1234,
  "url": "https://yezraelperez.es/wp-content/uploads/2026/05/foto.jpg",
  "edit_url": "https://yezraelperez.es/wp-admin/post.php?post=1234&action=edit",
  "title": "Atardecer en Cádiz"
}
```

## Configuración del Publish Service en Lightroom

Lightroom Classic no soporta nativamente endpoints REST custom. Hay tres rutas:

### Opción A — `lr/wordpress` (oficial Adobe + custom remote)

1. En Lightroom: **File → Plug-in Manager** → comprueba que está instalado el
   plugin "WordPress" oficial.
2. Crea un Publish Service apuntando a `https://yezraelperez.es/xmlrpc.php`
   (XML-RPC clásico). Esto NO usa nuestros endpoints, así que las fotos van a
   la librería de medios sin carpeta YZMF.

> Limitación: la integración nativa de WP+LR no soporta carpetas custom.
> Por eso recomendamos B o C.

### Opción B — Plugin `lr-yzmf` custom (recomendado)

Construir un `.lrplugin` propio que use estos endpoints. Estructura mínima:

```
lr-yzmf.lrplugin/
├── Info.lua            ← metadata del plugin
├── PublishServiceProvider.lua  ← define el servicio
└── PublishTask.lua     ← lógica de upload por foto
```

Variables clave en el `PublishServiceProvider`:

```lua
return {
    publishServiceProvider = {
        small_icon = 'icon.png',
        publish_fallbackNameBinding = 'pluginName',
        titleForPublishedCollection = 'Galería YZMF',
        titleForPublishedSmartCollection = 'Galería inteligente',
        getCollectionBehaviorInfo = function(publishSettings)
            return {
                defaultCollectionName = 'Sin carpeta',
                defaultCollectionCanBeDeleted = true,
                canAddCollection = true,
            }
        end,
        canAddCommentsToService = false,
    },
    sectionsForTopOfDialog = function(_, propertyTable)
        return {
            { title = 'YZMF Endpoint', synopsis = bind 'baseUrl',
              {
                LrView.osFactory():row { LrView.osFactory():static_text { title = 'URL: ' },
                                         LrView.osFactory():edit_field { value = bind 'baseUrl' } },
                LrView.osFactory():row { LrView.osFactory():static_text { title = 'Usuario: ' },
                                         LrView.osFactory():edit_field { value = bind 'username' } },
                LrView.osFactory():row { LrView.osFactory():static_text { title = 'App password: ' },
                                         LrView.osFactory():password_field { value = bind 'appPassword' } },
              }
            },
        }
    end,
}
```

`PublishTask.lua` envía cada foto con `LrHttp.postMultipart()`:

```lua
local LrHttp = import 'LrHttp'

function publishTask(exportContext)
    local exportSession = exportContext.exportSession
    local props = exportContext.propertyTable

    for i, rendition in exportContext:renditions() do
        local success, path = rendition:waitForRender()
        if success then
            local content = {
                { name = 'file',      filePath = path, fileName = LrPathUtils.leafName(path),
                  contentType = 'image/jpeg' },
                { name = 'folder_id', value = tostring(props.folderId or 0) },
                { name = 'title',     value = rendition.photo:getFormattedMetadata('title') },
                { name = 'caption',   value = rendition.photo:getFormattedMetadata('caption') },
                { name = 'alt',       value = rendition.photo:getFormattedMetadata('headline') },
            }
            local headers = {
                { field = 'Authorization',
                  value = 'Basic ' .. LrStringUtils.encodeBase64(props.username .. ':' .. props.appPassword) },
            }
            local response, hdrs = LrHttp.postMultipart(
                props.baseUrl .. '/wp-json/yzmf/v1/lightroom/publish',
                content, headers
            )
            -- Parsear JSON, obtener id, asociar via rendition:recordPublishedPhotoId(id)
        end
    end
end
```

> El plugin `.lrplugin` completo puede vivir en `tools/lr-yzmf.lrplugin/`
> dentro de este repo cuando se desarrolle.

### Opción C — Carpeta watch + script Python (más simple)

Si no quieres mantener un plugin Lua:

1. En Lightroom configura un Export Preset que vuelque a una carpeta local
   `~/LightroomExport/yzmf/`.
2. Un script Python con `watchdog` vigila esa carpeta y, por cada `.jpg`
   nuevo, hace POST al endpoint `/publish`.
3. Ejecutas el script en background o como servicio.

Ventaja: cero código Lua, funciona con cualquier exportador.
Inconveniente: no hay vinculación bidireccional (LR no sabe que ya publicaste).

```python
# scripts/lr-watcher.py (ejemplo)
import os, requests, time
from pathlib import Path
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler

ENDPOINT = 'https://yezraelperez.es/wp-json/yzmf/v1/lightroom/publish'
USER     = 'tu-usuario'
APPPWD   = 'xxxx xxxx xxxx xxxx'  # Application Password
FOLDER   = 12  # ID carpeta YZMF destino
WATCH    = Path.home() / 'LightroomExport' / 'yzmf'

class Handler(FileSystemEventHandler):
    def on_created(self, event):
        if event.is_directory: return
        if not event.src_path.lower().endswith(('.jpg','.jpeg','.png')): return
        with open(event.src_path, 'rb') as f:
            r = requests.post(ENDPOINT,
                files={'file': f},
                data={'folder_id': FOLDER},
                auth=(USER, APPPWD))
        print(event.src_path, '->', r.status_code, r.json().get('url'))

obs = Observer()
obs.schedule(Handler(), str(WATCH), recursive=False)
obs.start()
try:
    while True: time.sleep(60)
except KeyboardInterrupt:
    obs.stop()
obs.join()
```

## Test rápido

```bash
# 1. Comprobar que el endpoint responde (auth OK)
curl -u 'usuario:app password' \
  https://yezraelperez.es/wp-json/yzmf/v1/lightroom/check

# 2. Listar carpetas disponibles
curl -u 'usuario:app password' \
  https://yezraelperez.es/wp-json/yzmf/v1/lightroom/folders

# 3. Publicar una foto a una carpeta
curl -u 'usuario:app password' \
  -F file=@/ruta/foto.jpg \
  -F folder_id=12 \
  -F title='Atardecer Cádiz' \
  -F alt='Cielo naranja sobre el mar' \
  https://yezraelperez.es/wp-json/yzmf/v1/lightroom/publish
```

## Limitaciones conocidas

- El bridge de portfolios **no se sincroniza** automáticamente desde Lightroom.
  Las fotos van a una carpeta YZMF; el portfolio sigue siendo manual desde la
  PWA o el editor de WP.
- `remote_id` por ahora hace delete + re-upload. Una v2 podría sobreescribir
  el archivo manteniendo el mismo attachment ID (necesario si quieres mantener
  asociaciones a portfolios).
