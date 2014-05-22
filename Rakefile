desc "Minify ddh.js files for production. Generates ddh.min.js files"
task :minify_js do
  require 'uglifier'
  javascript_files = ["javascripts/ddh.js",
                      "samples/IIS samples/ddh_jp/ddh/javascripts/ddh.js"]
  javascript_files.each do |filepath|
    ugly_js = Uglifier.new.compile(File.read(filepath))
    puts "minify #{filepath}"
    File.new(filepath.sub("ddh.js", "ddh.min.js"), "w").write(ugly_js)
  end
end