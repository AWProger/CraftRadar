<?php
/**
 * CraftRadar — Античит: инструмент проверки на читы
 */

$pageTitle = 'Античит — Проверка на читы';
require_once __DIR__ . '/includes/header.php';
?>

<div class="anticheat-page">
    <div class="anticheat-hero">
        <div class="anticheat-icon">🛡️</div>
        <h1 class="anticheat-title">CraftRadar AntiCheat</h1>
        <p class="anticheat-subtitle">Инструмент для проверки игроков на наличие читов. Набор программ и данных для администраторов серверов Minecraft.</p>
    </div>

    <!-- Инструменты для скачивания -->
    <div class="anticheat-tools-btn" onclick="document.getElementById('toolsModal').style.display='flex'">
        📥 Скачать инструменты для проверки
    </div>

    <!-- Модалка с инструментами -->
    <div id="toolsModal" class="anticheat-modal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="anticheat-modal-content">
            <button class="anticheat-modal-close" onclick="this.parentElement.parentElement.style.display='none'">✕</button>
            <h2 style="text-align:center;color:var(--accent);font-family:var(--font-mc);font-size:0.8rem;margin-bottom:20px;">Инструменты проверки</h2>
            <div class="anticheat-tools-grid">
                <div class="anticheat-tool-card">
                    <div style="font-size:2rem;margin-bottom:10px;">🔍</div>
                    <h3>Process Hacker</h3>
                    <p>Мониторинг процессов, DLL-инъекций и сетевой активности. Позволяет обнаружить скрытые процессы читов.</p>
                    <a href="https://processhacker.sourceforge.io/downloads.php" target="_blank" rel="noopener" class="btn btn-sm btn-primary btn-block">Скачать</a>
                </div>
                <div class="anticheat-tool-card">
                    <div style="font-size:2rem;margin-bottom:10px;">⚙️</div>
                    <h3>System Informer</h3>
                    <p>Продвинутый системный монитор. Показывает все запущенные процессы, модули и сетевые соединения.</p>
                    <a href="https://systeminformer.sourceforge.io/" target="_blank" rel="noopener" class="btn btn-sm btn-primary btn-block">Скачать</a>
                </div>
                <div class="anticheat-tool-card">
                    <div style="font-size:2rem;margin-bottom:10px;">📊</div>
                    <h3>JournalTrace</h3>
                    <p>Анализ системных журналов и событий Windows. Отслеживание изменений файлов и реестра.</p>
                    <a href="https://github.com/ponei/JournalTrace/releases" target="_blank" rel="noopener" class="btn btn-sm btn-primary btn-block">Скачать</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Блоки с данными -->
    <div class="anticheat-section">
        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>📦 JAR Sizes</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="jarSizes">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="jarSizes">size:2263|size:5266|size:6515|size:6770|size:6778|size:7016|size:7218|size:7803|size:7891|size:9327|size:10283|size:10605|size:10958|size:11554|size:16541|size:17308|size:17339|size:18180|size:18527|size:18587|size:18734|size:19266|size:20578|size:20583|size:20639|size:20883|size:21161|size:21234|size:21664|size:22036|size:22861|size:26247|size:27546|size:27809|size:28084|size:28439|size:29304|size:29567|size:30279|size:31549|size:31607|size:34449|size:34669|size:35971|size:35993|size:38149|size:39017|size:39321|size:40142|size:42782|size:47159|size:48242|size:50828|size:51212|size:52426|size:54088|size:59381|size:62782|size:65316|size:65486|size:65765|size:66659|size:67491|size:68794|size:69757|size:72334|size:74105|size:80751|size:88896|size:95530|size:98811|size:100523|size:100799|size:101297|size:101571|size:101703|size:102297|size:102733|size:103761|size:104954|size:105623|size:105672|size:112386|size:120640|size:138417|size:143006|size:143597|size:143600|size:147329|size:147873|size:151762|size:153937|size:156722|size:156779|size:166677|size:169718|size:173698|size:183634|size:183651|size:192156|size:202720|size:257482|size:263070|size:267746|size:274865|size:300286|size:334588|size:343169|size:350629|size:409616|size:410358|size:517248|size:519731|size:532826|size:539151|size:556494|size:597406|size:636621|size:640838|size:878781|size:925493|size:1077149|size:1165063|size:1181556|size:1444714|size:1471429|size:1569093|size:1822841|size:3113569|size:3425801|size:3541075|size:3541138|size:3642292|size:3684385|size:4642998|size:5630483|size:7052171|size:7059952|size:22258750|size:25704986|size:26179274|size:26691896 *.jar</div>
        </div>

        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>⚖️ Size by Weight</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="sizeWeight">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="sizeWeight">size:9951744|size:24536064|size:15438336|size:6229504|size:6573056|size:7187456|size:7969792|size:1562249|size:1672329|size:1677449|size:1680521|size:147329|size:138351|size:202720|size:7788032|size:22885|size:23810|size:138351|size:147329|size:7988736|size:3711166|size:3697285|size:3712014|size:5641728|size:4413440|size:114974|size:111866|size:274865|size:1820884|size:5007380|size:6944256|size:5934592|size:2545664|size:2108662|size:1961742|size:3684385|size:5143837|size:4413440|size:116689|size:1968128|size:8011776|size:1883602|size:5918208|size:1897269|size:31445308|size:24390144|size:25158656|size:2023236|size:16836288|size:88065933|size:197933122|size:2258533|size:2305645|size:2372788|size:18764384|size:9400174|size:2363704|size:15445581|size:2373676|Baritone|Nursultan</div>
        </div>

        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>🏷️ Size by Name</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="sizeNames">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="sizeNames">impact | wurst | bleachhack | aristois | huzuni | skillclient | inertia | ares | sigma | meteor | liquidbounce | nurik | nursultan | celestial | calestial | celka | expensive | neverhook | excellent | wexside | wildclient | minced | deadcode | akrien | jigsaw | future | jessica | dreampool | norules | konas | richclient | rusherhack | thunderhack | moonhack | doomsday | nightware | ricardo | extazyy | troxill | antileak | arbuz | .akr | .wex | dauntiblyat | rename_me_please | editme | takker | fuzeclient | wisefolder | flauncher | vec.dll | USBOblivion.exe | Feather | delta | venus | baritone | spambot | CleanCut | spam_bot | inventory_walk | player_highlighter | aimbot | freecam | bedrock_breaker_mode | viaversion | double_hotbar | elytra_swap | armor_hotswap | smart_moving | chest | savesearcher | topkautobuy | topkaautobuy | tweakeroo | mob_hitbox | librarian_trade_finder | sacurachorusfind | autoattack | entity_outliner | invmove | viabackwards | viarewind | viafabric | viaforge | viaproxy | vialoader | viamcp | hitbox | elytrahack | DiamondSim | ForgeHax | clientcommands | Control-Tweaks | SwingThroughGrass | CutThrough | Haruka | NewLauncher | Blade | Hachclient | Inertia | Fluger | Exloader</div>
        </div>

        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>🔗 Size Vec.dll</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="sizeVec">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="sizeVec">size:30720 utf8content:net/minecraft/client/entity/player/ClientPlayerEntity|net/minecraft/util/math/AxisAlignedBB</div>
        </div>

        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>💀 Size DoomsDay Client</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="sizeDoomsday">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="sizeDoomsday">ext:jar size:21kb-10mb content:"l.png" content:"mcmod.info"</div>
        </div>

        <div class="anticheat-block">
            <div class="anticheat-block-header">
                <h2>⚡ Size .exe</h2>
                <button class="btn btn-sm btn-outline anticheat-copy" data-target="sizeExe">📋 Копировать</button>
            </div>
            <div class="anticheat-block-content" id="sizeExe">.exe size:12mb-25mb</div>
        </div>
    </div>

    <!-- Инструкция -->
    <div class="card" style="margin-top: 24px;">
        <h2 class="section-title">📋 Как использовать</h2>
        <ol style="color: var(--text-muted); line-height: 2; padding-left: 20px;">
            <li>Скачайте один из инструментов (Process Hacker, System Informer или JournalTrace)</li>
            <li>Попросите игрока запустить инструмент на своём компьютере</li>
            <li>Скопируйте нужный блок данных (JAR Sizes, Size by Name и т.д.)</li>
            <li>Вставьте данные в поиск инструмента для сканирования файлов</li>
            <li>Если найдены совпадения — игрок использует читы</li>
        </ol>
        <p style="color: var(--text-muted); margin-top: 12px; font-size: 0.85rem;">
            💡 <strong>JAR Sizes</strong> — поиск по размерам известных чит-клиентов<br>
            💡 <strong>Size by Name</strong> — поиск по названиям читов (impact, wurst, meteor и др.)<br>
            💡 <strong>Vec.dll / DoomsDay</strong> — специфичные сигнатуры конкретных читов<br>
            💡 <strong>Size .exe</strong> — поиск подозрительных исполняемых файлов
        </p>
    </div>
</div>

<style>
    .anticheat-page { max-width: 900px; margin: 0 auto; }

    .anticheat-hero {
        text-align: center;
        padding: 30px 0;
    }
    .anticheat-icon { font-size: 3rem; margin-bottom: 12px; }
    .anticheat-title {
        font-family: var(--font-mc);
        font-size: 1rem;
        color: var(--accent);
        text-shadow: 2px 2px 0 rgba(0,0,0,0.5);
        margin-bottom: 8px;
    }
    .anticheat-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Кнопка скачивания инструментов */
    .anticheat-tools-btn {
        display: block;
        max-width: 400px;
        margin: 0 auto 30px;
        padding: 14px 24px;
        background: var(--accent);
        color: #000;
        text-align: center;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        border: var(--pixel-border) var(--accent);
        box-shadow: 4px 4px 0 rgba(0,255,128,0.3);
        transition: all var(--transition);
    }
    .anticheat-tools-btn:hover {
        transform: translateY(-2px);
        box-shadow: 6px 6px 0 rgba(0,255,128,0.4);
    }

    /* Модалка */
    .anticheat-modal {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.8);
        z-index: 200;
        align-items: center;
        justify-content: center;
    }
    .anticheat-modal-content {
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        padding: 30px;
        max-width: 700px;
        width: 90%;
        position: relative;
        box-shadow: 8px 8px 0 rgba(0,0,0,0.5);
    }
    .anticheat-modal-close {
        position: absolute; top: 10px; right: 10px;
        background: none; border: 2px solid var(--danger); color: var(--danger);
        width: 30px; height: 30px; cursor: pointer; font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
    }
    .anticheat-tools-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .anticheat-tool-card {
        background: var(--bg);
        border: var(--pixel-border) var(--border);
        padding: 16px;
        text-align: center;
    }
    .anticheat-tool-card h3 {
        font-size: 0.85rem;
        color: var(--accent);
        margin-bottom: 8px;
    }
    .anticheat-tool-card p {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 12px;
        line-height: 1.5;
    }

    /* Блоки данных */
    .anticheat-section { display: flex; flex-direction: column; gap: 16px; }
    .anticheat-block {
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .anticheat-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 2px solid var(--border);
        background: rgba(0,255,128,0.02);
    }
    .anticheat-block-header h2 {
        font-family: var(--font-mc);
        font-size: 0.65rem;
        color: var(--accent);
    }
    .anticheat-block-content {
        padding: 14px 16px;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 0.75rem;
        color: var(--text-muted);
        line-height: 1.6;
        word-break: break-all;
        max-height: 200px;
        overflow-y: auto;
        user-select: all;
    }

    @media (max-width: 768px) {
        .anticheat-tools-grid { grid-template-columns: 1fr; }
        .anticheat-block-header { flex-direction: column; gap: 8px; align-items: flex-start; }
        .anticheat-title { font-size: 0.8rem; }
    }
</style>

<script>
document.querySelectorAll('.anticheat-copy').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.dataset.target);
        if (!target) return;
        navigator.clipboard.writeText(target.textContent.trim()).then(function() {
            btn.textContent = '✓ Скопировано';
            btn.classList.remove('btn-outline');
            btn.classList.add('btn-primary');
            setTimeout(function() {
                btn.textContent = '📋 Копировать';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            }, 2000);
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
