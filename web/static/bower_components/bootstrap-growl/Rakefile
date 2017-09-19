# special thanks to https://github.com/tuupola/jquery_lazyload for this cool Rakefile
task :default => [:minify]

desc "coffee2js"
task :coffee2js do
  coffee2js
end

desc "Minify"
task :minify do
  minify
end

desc "Build"
task :build do
  coffee2js
  minify
end

# method definitions

def coffee2js
  begin
    require 'coffee-script'
  rescue LoadError => e
    if verbose
      puts "\nYou'll need the 'coffee-script' gem for translate the coffeescript file to js. Just run:\n\n"
      puts "  $ gem install coffee-script"
      puts "\nand you should be all set.\n\n"
      exit
    end
    return false
  end
  puts "Translating jquery.bootstrap-growl.coffee to jquery.bootstrap-growl.js..."
  File.open("jquery.bootstrap-growl.js", "w") do |f|
    f.puts CoffeeScript.compile(File.read("jquery.bootstrap-growl.coffee"))
  end
end

def minify
  begin
    require 'uglifier'
  rescue LoadError => e
    if verbose
      puts "\nYou'll need the 'uglifier' gem for minification. Just run:\n\n"
      puts "  $ gem install uglifier"
      puts "\nand you should be all set.\n\n"
      exit
    end
    return false
  end
  puts "Minifying jquery.bootstrap-growl.js with UglifyJS..."
  File.open("jquery.bootstrap-growl.min.js", "w") do |f|
    f.puts Uglifier.new.compile(File.read("jquery.bootstrap-growl.js"))
  end
end

