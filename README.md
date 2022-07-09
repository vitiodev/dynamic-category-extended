# Dynamic Categories Extended

This module add new settings to admin menu with these options: (Stores->Configuration->Dynamic Category)
1. **Use query instead validation** - by default, module Dynamic Categories uses for validation rule validation system. If you have a lot of products, this
takes a long time. Instead rule validation, we use query request to db.
2. **Use custom logic for indexer** - this option gives us possibility to use magento queue for save category via async request.
3. **Rewrite urls during indexation** - if you don't have category in your product url, you can turn off this functionality. It's takes a long time.
4. **Check, if exist product attribute** - this functionality hepls us to check product attribute if exist.

## Notice.

This module used only for one website. It's webiste = 1.
