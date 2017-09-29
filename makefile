version = 3_0_00
outfile = newsletter2go_$(version).ocmod.zip

$(version): $(outfile)

$(outfile):
	zip -r  build.zip ./upload/* ./install.xml
	mv build.zip $(outfile)
clean:
	rm -rf tmp