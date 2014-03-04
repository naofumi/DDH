task :minify_js do
  require 'uglifier'
  ugly_js = Uglifier.new.compile(File.read("javascripts/ddh.js"))
  File.new("javascripts/ddh.min.js", "w").write(ugly_js)
end