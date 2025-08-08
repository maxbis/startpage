// Section Expand/Collapse functionality
const expandIndicators = document.querySelectorAll('.expand-indicator');

expandIndicators.forEach(indicator => {
  DEBUG.log('SECTION', 'Adding click listener to indicator:', indicator.dataset.sectionId);
  indicator.addEventListener('click', (e) => {
    e.stopPropagation(); // Prevent triggering drag events
    
    const sectionId = indicator.dataset.sectionId;    
    const section = document.querySelector(`section[data-category-id="${sectionId}"]`);    
    const content = section.querySelector('.section-content');
    
    if (content.classList.contains('expanded')) {
      DEBUG.log('SECTION', 'Collapsing section...');
      // Collapse
      content.classList.remove('expanded');
      indicator.classList.remove('expanded');
    } else {
      DEBUG.log('SECTION', 'Expanding section...');
      // Expand
      content.classList.add('expanded');
      indicator.classList.add('expanded');
    }
  });
});
