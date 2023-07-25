<form method="post" action="<!-- www -->/?q=article/register">
    <div class="row">
        <div class="twelve columns">
            <label for="article-title">Article title</label>
            <input type="text" name="article-title" id="article-title" class="u-full-width" />
        </div>
        <div class="twelve columns">
            <label for="article-body">Article body</label>
            <textarea name="article-body" id="article-body" class="u-full-width"></textarea>
        </div>
        <div class="twelve columns">
            <input type="submit" value="submit" class="button-primary" />
        </div>
    </div>
</form>
<link rel="stylesheet" type="text/css" href="<!-- path -->/simditor/simditor.css" />
<script type="text/javascript" src="<!-- path -->/simditor/jquery.min.js"></script>
<script type="text/javascript" src="<!-- path -->/simditor/module.js"></script>
<script type="text/javascript" src="<!-- path -->/simditor/hotkeys.js"></script>
<script type="text/javascript" src="<!-- path -->/simditor/uploader.js"></script>
<script type="text/javascript" src="<!-- path -->/simditor/simditor.js"></script><script>
    var editor = new Simditor({
        textarea: get("article-body")
        //optional options
    });
</script>