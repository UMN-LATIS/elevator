require("http").get({
    host: "fontawesome.io",
    path: "/assets/font-awesome/css/font-awesome.min.css"
}, function(response) {
    var body = "";
    response.on("data", function(chunk) { body += chunk });
    response.on("end", function() {
        var version = body.match(/^ \*  Font Awesome ([\d.]+)/m);
        if (!version) { console.log("Couldn't parse version"); return process.exit(1); }
        else version = version[1];
        
        console.log("Font Awesome v" + version);
        var package = require("fs").readFileSync(__dirname + "/package.json").toString();
        package = package.replace(/(\"version\":\s*\")[\d.]+\"/, "$1"+version+"\"");
        require("fs").writeFileSync(__dirname + "/package.json", package);

        var fa = "var fa=function(i){return fa[i.replace(/-./g,function(x){return x.substr(1).toUpperCase()})]};"

        var namecount = 0;
        var main = body.match(/(\}\.fa-[a-z\-]+(?::before,\.fa-[a-z\-]+)*):before\{content:"\\([0-9a-f]+)"/g);
        if (!main) { console.log("Couldn't parse icons"); return process.exit(1); }
        for (var i = 0; i < main.length; i++) {
            var icon = main[i].match(/(\}\.fa-[a-z\-]+(?::before,\.fa-[a-z\-]+)*):before\{content:"\\([0-9a-f]+)"/).slice(1);
            icon[0] = icon[0].substr(5).split(/:before,\.fa-/);
            for (var k = 0; k < icon[0].length; k++) {
                icon[0][k] = icon[0][k].replace(/-./g, function(x) { return x.substr(1).toUpperCase() });
                fa += "fa." + icon[0][k] + "=\"\\u" + icon[1] + "\";";
            }
            namecount += icon[0].length;
        }

        fa += "module.exports=fa;";
        require("fs").writeFileSync(__dirname + "/index.js", fa);

        console.log(main.length + " icons parsed (" + namecount + " names)")
    });
});
