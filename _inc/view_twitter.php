<div class="box_top">Twitter de <?php echo ucfirst($_GET['view']); ?></div>
<div class="box_middle">Últimos posts do twitter de <?php echo ucfirst($_GET['view']); ?>, em tempo real.<div class="sep"></div>
<script src="http://widgets.twimg.com/j/2/widget.js"></script>
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 5,
  interval: 6000,
  width: 'auto',
  height: 'auto',
  theme: {
    shell: {
      background: '#282828',
      color: '#bbbbbb'
    },
    tweets: {
      background: '#151515',
      color: '#bbbbbb',
      links: '#eeeeee'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    hashtags: true,
    timestamp: true,
    avatars: true,
    behavior: 'all'
  }
}).render().setUser('<?php echo $db['config_twitter']; ?>').start();
</script>
</div>
<div class="box_bottom"></div>