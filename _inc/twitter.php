<div class="box_top">Meu Twitter</div>
<div class="box_middle">Mostre os últimos posts de seu twitter em tempo real para quem desejar!<div class="sep"></div>
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