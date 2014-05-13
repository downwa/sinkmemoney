gitconfig:
	git config --global push.default simple
	git config --global credential.helper 'cache --timeout=3600'

checkout:
	#git fetch origin
	#git merge origin/master
	git pull

checkin: # e.g. downwa
	#git push -v origin master
	#git push -v https://downwa@github.com/downwa/radioplay master
	git add -v *
	git commit -v
	git push
