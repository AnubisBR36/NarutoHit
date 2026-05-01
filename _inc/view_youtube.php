<?php
$yt = preg_replace('/[^A-Za-z0-9_\-]/', '', $db['config_youtube'] ?? '');
$view_name = htmlspecialchars(ucfirst($_GET['view'] ?? ''), ENT_QUOTES, 'UTF-8');
if($yt === '') return;

$is_channel_id = (strlen($yt) === 24 && strpos($yt, 'UC') === 0);
$uploads_pl    = $is_channel_id ? ('UU' . substr($yt, 2)) : '';
$channel_url   = $is_channel_id
    ? 'https://www.youtube.com/channel/' . $yt
    : 'https://www.youtube.com/@' . $yt;
$player_id     = 'yt_player_' . substr(md5($yt), 0, 8);

// Buscar dados do canal via RSS público (sem API key) — cache de 1h
$ch_name      = '';
$ch_videos    = []; // [ ['id'=>..., 'title'=>...], ... ]
if($is_channel_id){
    $cache_dir = __DIR__ . '/../_cache';
    if(!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
    $cache_file = $cache_dir . '/yt_' . $yt . '.json';
    $cached = null;
    if(is_file($cache_file) && (time() - filemtime($cache_file) < 3600)){
        $cached = @json_decode(file_get_contents($cache_file), true);
    }
    if(!$cached || !isset($cached['videos'])){
        $rss_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $yt;
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'user_agent' => 'Mozilla/5.0']]);
        $xml_raw = @file_get_contents($rss_url, false, $ctx);
        if($xml_raw){
            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string($xml_raw);
            if($xml){
                $author = (string)($xml->author->name ?? '');
                $vids = [];
                foreach($xml->entry as $entry){
                    $vid_id = (string)($entry->children('yt', true)->videoId ?? '');
                    $vid_ttl = (string)($entry->title ?? '');
                    if($vid_id !== '') $vids[] = ['id'=>$vid_id, 'title'=>$vid_ttl];
                    if(count($vids) >= 5) break;
                }
                $cached = ['name'=>$author, 'videos'=>$vids];
                @file_put_contents($cache_file, json_encode($cached));
            }
        }
        if(!$cached) $cached = ['name'=>'', 'videos'=>[]];
    }
    $ch_name   = $cached['name'] ?? '';
    $ch_videos = $cached['videos'] ?? [];
}
$first_video = $ch_videos[0] ?? null;
$ch_thumb    = $first_video ? ('https://i.ytimg.com/vi/' . $first_video['id'] . '/mqdefault.jpg') : '';
?>
<div class="box_top">YouTube de <?php echo $view_name; ?></div>
<div class="box_middle">
    <?php if($is_channel_id && ($ch_name !== '' || $ch_thumb !== '')): ?>
        <a href="<?php echo htmlspecialchars($channel_url); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;background:linear-gradient(90deg,#1a0000 0%,#330000 50%,#1a0000 100%);border:1px solid #FF0000;border-radius:6px;padding:8px 10px;text-decoration:none;color:#fff;margin-bottom:8px;">
            <?php if($ch_thumb !== ''): ?>
                <img src="<?php echo htmlspecialchars($ch_thumb); ?>" alt="" style="width:64px;height:48px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid #555;" loading="lazy" />
            <?php else: ?>
                <div style="width:48px;height:48px;background:#FF0000;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px;">▶</div>
            <?php endif; ?>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:bold;color:#fff;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?php echo $ch_name !== '' ? htmlspecialchars($ch_name) : ('Canal de ' . $view_name); ?>
                </div>
                <?php if($first_video): ?>
                    <div style="color:#bbb;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                        Último vídeo: <?php echo htmlspecialchars($first_video['title']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="color:#FF0000;font-weight:bold;font-size:11px;white-space:nowrap;">▶ Visitar canal ↗</div>
        </a>
    <?php else: ?>
        <div style="text-align:center;margin-bottom:6px;">
            <a href="<?php echo htmlspecialchars($channel_url); ?>" target="_blank" rel="noopener" style="color:#FF0000;text-decoration:none;font-weight:bold;">
                ▶ Visitar canal ↗
            </a>
        </div>
    <?php endif; ?>

    <?php if($is_channel_id): ?>
        <div id="<?php echo $player_id; ?>_wrap" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;background:#000;">
            <iframe
                id="<?php echo $player_id; ?>"
                src="https://www.youtube-nocookie.com/embed/videoseries?list=<?php echo htmlspecialchars($uploads_pl); ?>&enablejsapi=1"
                style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy"></iframe>
        </div>

        <?php if(count($ch_videos) > 1): ?>
            <div style="margin-top:10px;">
                <div style="color:#FFD700;font-weight:bold;font-size:12px;margin-bottom:6px;border-bottom:1px solid #444;padding-bottom:3px;">
                    📺 Vídeos Recentes
                </div>
                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px;">
                    <?php foreach($ch_videos as $v):
                        $thumb = 'https://i.ytimg.com/vi/' . $v['id'] . '/mqdefault.jpg';
                        $vurl  = 'https://www.youtube.com/watch?v=' . $v['id'];
                    ?>
                        <a href="<?php echo htmlspecialchars($vurl); ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars($v['title']); ?>" style="display:block;text-decoration:none;color:#ddd;border:1px solid #333;border-radius:4px;overflow:hidden;background:#0a0a0a;transition:border-color .15s;" onmouseover="this.style.borderColor='#FF0000'" onmouseout="this.style.borderColor='#333'">
                            <div style="position:relative;padding-bottom:56.25%;background:#000;">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;" loading="lazy" />
                                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:28px;height:28px;background:rgba(255,0,0,0.85);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;">▶</div>
                            </div>
                            <div style="padding:4px 5px;font-size:10px;line-height:1.2;height:26px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                                <?php echo htmlspecialchars($v['title']); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <script>
        (function(){
            var wrap = document.getElementById('<?php echo $player_id; ?>_wrap');
            if(!wrap) return;
            if(!window.__ytApiLoading){
                window.__ytApiLoading = true;
                var tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(tag);
            }
            function attach(){
                try {
                    new YT.Player('<?php echo $player_id; ?>', {
                        events: {
                            'onError': function(e){ wrap.style.display = 'none'; }
                        }
                    });
                } catch(err) {}
            }
            if(window.YT && window.YT.Player){
                attach();
            } else {
                var prev = window.onYouTubeIframeAPIReady;
                window.onYouTubeIframeAPIReady = function(){
                    if(typeof prev === 'function') prev();
                    attach();
                };
            }
        })();
        </script>
    <?php endif; ?>
</div>
<div class="box_bottom"></div>
