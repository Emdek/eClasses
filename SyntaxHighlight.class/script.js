window.addEventListener('load', function()
{
	var elements = document.getElementsByClassName('highlight');

	for (var i = 0; i < elements.length; ++i)
	{
		var options = (elements[i].hasAttribute('data-options') ? elements[i].getAttribute('data-options').split(',') : []);

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
