if [ ! $1 ]; then
        echo "Usage: mk.xsltproc.sh <existing_output_directory>";
        exit 1;
fi
xsltproc -o $1 stylesheet.xml manual.xml
