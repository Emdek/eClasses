window.addEventListener('load', function()
{
	function getOffset(element)
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

		return offset;
	}

	function getLine(element, event)
	{
		return Math.floor((((event.pageX === undefined) ? (event.clientY + document.body.scrollTop - document.body.clientTop) : event.pageY) - getOffset(element)) / 16);
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

		var elements = element.getElementsByTagName('pre');
		elements[elements.length - 1].style.background = backgrounds.join(',');
	}

	function updateSize(element)
	{
		element.getElementsByClassName('numbers')[0].style.height = ((element.clientHeight - 6) + 'px');
	}

	var background = 'url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAgCAYAAADT5RIaAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAACHRFWHRDb21tZW50APbMlr8AAAARSURBVAhbY2CgNsj9zE5FAgB9dRZxBYoY7AAAAABJRU5ErkJggg==\') 0 0';
	var elements = document.getElementsByClassName('code');

	for (var i = 0; i < elements.length; ++i)
	{
		var options = (elements[i].hasAttribute('data-options') ? elements[i].getAttribute('data-options').split(',') : []);

		if (options.indexOf('linenumbers') >= 0)
		{
			updateSize(elements[i]);

			elements[i].addEventListener('resize', function()
			{
				updateSize(this);
			});
		}

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
			var foldables = elements[i].getElementsByClassName('foldable');
 			var container = elements[i].getElementsByClassName('numbers')[0];
 			var offset = getOffset(container);

			for (var j = 0; j < foldables.length; ++j)
			{
				var fold = document.createElement('span');
				fold.setAttribute('data-range', j);
				fold.className = 'fold';
				fold.style.top = ((getOffset(foldables[j]) - offset) + 'px');

				fold.addEventListener('click', function(event)
				{
					var range = this.parentNode.parentNode.getElementsByClassName('foldable')[parseInt(this.getAttribute('data-range'))];
					var show = (range.style.visibility == 'hidden');

					range.style.visibility = (show ? 'inherit' : 'hidden');

					this.className = ((show ? 'fold' : 'unfold') + ' indicator');

					event.stopPropagation();
				});
				fold.addEventListener('mouseover', function(event)
				{
					var range = this.parentNode.parentNode.getElementsByClassName('foldable')[parseInt(this.getAttribute('data-range'))];
					range.classList.add('highlight');

					this.style.height = (range.getBoundingClientRect().height + 'px');
					this.classList.add('indicator');
				});
				fold.addEventListener('mouseout', function(event)
				{
					this.parentNode.parentNode.getElementsByClassName('foldable')[parseInt(this.getAttribute('data-range'))].classList.remove('highlight');
					this.style.height = '12px';
					this.classList.remove('indicator');
				});

				container.appendChild(fold);
			}
		}

		if (options.indexOf('ranges') >= 0)
		{
			var ranges = elements[i].querySelectorAll('.range > span:first-child, .range > span:last-child');

			for (var j = 0; j < ranges.length; ++j)
			{
				ranges[j].addEventListener('mouseover', function()
				{
					this.parentNode.classList.add('highlight');
				});
				ranges[j].addEventListener('mouseout', function()
				{
					this.parentNode.classList.remove('highlight');
				});
			}
		}
	}
});
