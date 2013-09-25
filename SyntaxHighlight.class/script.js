window.addEventListener('load', function()
{
	function getLine(element, event)
	{
		var offset = 0;

		if (element.offsetParent)
		{
			do
			{
				offset += element.offsetTop;
			}
			while (element = element.offsetParent);
		}

		return Math.floor((((event.pageX === undefined) ? (event.clientY + document.body.scrollTop - document.body.clientTop) : event.pageY) - offset) / 16);
	}

	function updateBackground(element)
	{
		var backgrounds = [];

		if (element.hasAttribute('data-activeline'))
		{
			backgrounds.push('url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAQCAYAAADXnxW3AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAACHRFWHRDb21tZW50APbMlr8AAAAQSURBVAgdY7izxUGSgXoEANz0HpFLae3eAAAAAElFTkSuQmCC\') repeat-x 0 ' + (element.getAttribute('data-activeline') * 16) + 'px');
		}

		if (element.hasAttribute('data-marklines') && element.getAttribute('data-marklines') !== '')
		{
			var marked = element.getAttribute('data-marklines').split(',');

			for (var i = 0; i < marked.length; ++i)
			{
				backgrounds.push('url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAQCAYAAADXnxW3AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAACHRFWHRDb21tZW50APbMlr8AAAAQSURBVAgdY3jX9E6SgXoEADrCJ3EYSSlAAAAAAElFTkSuQmCC\') repeat-x 0 ' + (marked[i] * 16) + 'px');
			}
		}

		backgrounds.push(background);

		element.getElementsByClassName('code')[0].style.background = backgrounds.join(',');
	}

	var background = 'url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAgCAYAAADT5RIaAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAACHRFWHRDb21tZW50APbMlr8AAAARSURBVAhbY2CgNsj9zE5FAgB9dRZxBYoY7AAAAABJRU5ErkJggg==\') 0 0';
	var elements = document.getElementsByClassName('highlight');

	for (var i = 0; i < elements.length; ++i)
	{
		var options = (elements[i].hasAttribute('data-options') ? elements[i].getAttribute('data-options').split(',') : []);

		if (options.indexOf('marklines') >= 0)
		{
			elements[i].addEventListener('click', function(event)
			{
				var line = getLine(this, event).toString();
				var marked = ((this.hasAttribute('data-marklines') && this.getAttribute('data-marklines') !== '') ? this.getAttribute('data-marklines').split(',') : []);
				var position = marked.indexOf(line);

				if (position >= 0)
				{
					marked.splice(position, 1);
				}
				else
				{
					marked.push(line);
				}

				this.setAttribute('data-marklines', marked.join(','));

				updateBackground(this);
			});
		}

		if (options.indexOf('activeline') >= 0)
		{
			elements[i].addEventListener('mousemove', function(event)
			{
				this.setAttribute('data-activeline', getLine(this, event));

				updateBackground(this);
			});
			elements[i].addEventListener('mouseout', function(event)
			{
				this.removeAttribute('data-activeline');

				updateBackground(this);
			});
		}

		if (options.indexOf('folding') >= 0)
		{
			var switchers = elements[i].getElementsByClassName('switcher');

			for (var j = 0; j < switchers.length; ++j)
			{
				switchers[j].addEventListener('click', function()
				{
					this.nextSibling.style.visibility = ((this.nextSibling.style.visibility == 'hidden') ? 'visible' : 'hidden');
				});
			}
		}

		if (options.indexOf('ranges') >= 0)
		{
			var ranges = elements[i].getElementsByClassName('range');

			for (var j = 0; j < ranges.length; ++j)
			{
				ranges[j].addEventListener('mouseover', function()
				{
					this.parentNode.className = 'highlightrange';
				});
				ranges[j].addEventListener('mouseout', function()
				{
					this.parentNode.className = '';
				});
			}
		}
	}
});
