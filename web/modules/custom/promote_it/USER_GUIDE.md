# Promote It User Guide

This guide provides step-by-step instructions for content editors and site builders on how to use the Promote It module to manage promoted content ordering on your Drupal site.

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Setting Up Content Types](#setting-up-content-types)
4. [Managing Promoted Content](#managing-promoted-content)
5. [Working with Weight Values](#working-with-weight-values)
6. [Using in Views](#using-in-views)
7. [Common Workflows](#common-workflows)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)
10. [Frequently Asked Questions](#frequently-asked-questions)

---

## Introduction

### What is Promote It?

Promote It is a Drupal module that helps you control the order in which promoted content appears on your site. Instead of promoted content showing in a random order (or by publish date), you can manually arrange it exactly how you want.

### What Can You Do With It?

- **Drag and drop** to reorder promoted content
- **Feature important articles** by giving them lower weight values (they appear first)
- **Demote content** without unpublishing by increasing weight values
- **Mix content types** - promote articles, pages, and more in one list
- **Manually override** automatic ordering when needed

### Who Is This For?

- **Content Editors**: Manage front page featured content
- **Site Builders**: Configure which content types support weighted promotion
- **Developers**: Integrate promoted content in custom themes and modules (see [API.md](API.md))

---

## Getting Started

### Prerequisites

Before you can use Promote It, make sure:

1. The module is installed and enabled on your site
2. You have the **"Manage promoted content order"** permission
3. At least one content type has weighted promotion enabled

### Quick Start (5 Minutes)

1. **Enable weighted promotion** on the Article content type:
   - Go to **Structure** → **Content types** → **Article** → **Edit**
   - Find the **Promote It** section
   - Check **"Enable promotion weight field"**
   - Click **Save content type**

2. **Promote some articles**:
   - Edit an article, check **"Promoted to front page"**, and save
   - Repeat for 3-5 more articles

3. **Arrange them**:
   - Go to **Content** → **Promote it** (`/admin/content/promote`)
   - Drag articles into your preferred order
   - Click **Save**

4. **See the results**:
   - View your front page or any view showing promoted content
   - Content should appear in the order you specified

---

## Setting Up Content Types

### Enabling Weighted Promotion

To enable weighted promotion on a content type:

1. Navigate to **Structure** → **Content types** (`/admin/structure/types`)
2. Click **Edit** next to the content type (e.g., "Article")
3. Scroll to the **Promote It** section
4. Check the box: **"Enable promotion weight field"**
5. Click **Save content type**

**What Happens:**
- A field called `field_promotion_weight` is automatically added
- The field appears in the "Promotion options" section on node forms
- The content type now appears in the Promote It management interface

### Multiple Content Types

You can enable weighted promotion on multiple content types:

- **Article** - For blog posts and news
- **Page** - For featured pages
- **Event** - For upcoming events
- **Product** - For featured products

All enabled content types will appear together in the **Promote it** interface, where you can mix and order them as needed.

### Disabling Weighted Promotion

To remove weighted promotion from a content type:

1. Go to **Structure** → **Content types** → Select content type → **Edit**
2. In the **Promote It** section, uncheck **"Enable promotion weight field"**
3. Click **Save content type**

**Warning:** This permanently removes the promotion weight field and all weight data for that content type!

---

## Managing Promoted Content

### The Promote It Interface

Access the main interface at **Content** → **Promote it** or visit `/admin/content/promote`.

#### Interface Overview

```
+------------------------------------------------------------------+
|  Promote Articles                                                 |
+------------------------------------------------------------------+
|  Add content: [Search for content...]          [Add Row]         |
+------------------------------------------------------------------+
|  ⠿  Node                        | Promoted | Weight   | Remove   |
|  ═══════════════════════════════════════════════════════════════ |
|  ⠿  Breaking News Story         |    ☑     |   -10    | [Remove] |
|  ⠿  Feature Article Today       |    ☑     |     0    | [Remove] |
|  ⠿  Important Update            |    ☐     |     5    | [Remove] |
|  ⠿  Older Article              |    ☑     |    10    | [Remove] |
+------------------------------------------------------------------+
|                                            [Save] [Reset]         |
+------------------------------------------------------------------+
```

### Adding Content to the List

1. **Use the Autocomplete Field**:
   - Start typing the title of a node
   - Select from the dropdown suggestions
   - Only **unpromoted** content appears in suggestions

2. **Click "Add Row"**:
   - Adds the selected content to the table
   - Content is added with the next available weight

3. **Check the "Promoted" Checkbox**:
   - Checking the box marks content as promoted
   - Unchecking unpromotes content

### Reordering Content

**Drag and Drop**:
1. Hover over the drag handle (⠿) at the left of each row
2. Click and hold
3. Drag the row up or down
4. Release to drop it in the new position
5. Click **Save** to apply changes

**Tips:**
- The table automatically renumbers weights as you drag
- Content at the top of the table (lowest weight) appears first on your site
- You can drag multiple items at once if needed

### Removing Content

**Option 1: Unpromote (keeps in list)**
- Uncheck the **Promoted** checkbox
- Content stays in the table but won't appear on front page

**Option 2: Remove from Table**
- Click the **Remove** button in the row
- Content is removed from the management interface
- Content is automatically unpromoted

### Saving Changes

Always click **Save** after making changes:
- Weight values are updated on nodes
- Promote/unpromote status is applied
- Changes take effect immediately
- You'll see a success message confirming the update

---

## Working with Weight Values

### Understanding Weights

Weights determine the order of promoted content:
- **Lower weights** (e.g., -50, -10, 0) appear **first**
- **Higher weights** (e.g., 10, 50, 100) appear **last**
- **Same weight** - order is unpredictable (avoid duplicates)

### Weight Range

Valid weights: **-100 to 100**

Common weight strategies:

| Weight | Usage                           |
|--------|---------------------------------|
| -100   | Emergency/breaking news         |
| -50    | Urgent/priority content         |
| -10    | Important featured content      |
| 0      | Default promoted content        |
| 10     | Secondary featured content      |
| 50     | Lower priority                  |
| 100    | Demoted but still promoted      |

### Setting Weights Manually

You can set weights directly on the node edit form:

1. Edit any promoted node
2. Expand the **Promotion options** sidebar
3. Find **Promotion weight** field
4. Enter a number between -100 and 100
5. Save the node

**When to Use Manual Weights:**
- Quick adjustment without visiting Promote It interface
- Programmatic updates via code
- Bulk operations via Drush or custom scripts

---

## Using in Views

### Adding Promoted Content to a View

1. **Create or Edit a View**:
   - Go to **Structure** → **Views**
   - Create a new view or edit an existing one

2. **Add Filter for Promoted Content**:
   - Click **Add** in the **Filter Criteria** section
   - Search for "Promoted to front page"
   - Configure: `Promoted = Yes`
   - Click **Apply**

3. **Sort by Weight**:
   - Click **Add** in the **Sort Criteria** section
   - Search for "Promotion weight"
   - Choose **Ascending** (low to high) or **Descending** (high to low)
   - Click **Apply**

4. **Save the View**

### Example: Front Page Featured Content

Create a block showing the top 5 promoted articles:

1. **Create View**:
   - Type: Content
   - Display: Block

2. **Filters**:
   - Content Type = Article
   - Promoted = Yes
   - Published = Yes

3. **Sort**:
   - Promotion weight (ascending)

4. **Items to Display**: 5

5. **Place Block**:
   - Go to **Structure** → **Block layout**
   - Place the block in a region (e.g., "Content")

---

## Common Workflows

### Workflow 1: Featuring New Content

**Scenario**: You've just published a breaking news article that needs to be at the top.

**Steps**:
1. Edit the article
2. Check **"Promoted to front page"**
3. Set **Promotion weight** to `-100` (or very low number)
4. Save

**Alternative**:
1. Go to **Content** → **Promote it**
2. Search for the article in the autocomplete
3. Check **Promoted**
4. Drag it to the top of the list
5. Click **Save**

### Workflow 2: Weekly Content Rotation

**Scenario**: Every Monday, you want to rotate featured content.

**Steps**:
1. Go to **Content** → **Promote it**
2. Unpromote last week's featured content (uncheck boxes)
3. Add new articles for this week (via autocomplete)
4. Arrange in preferred order
5. Click **Save**

### Workflow 3: Demoting Old Content

**Scenario**: An article is no longer urgent but should stay promoted.

**Steps**:
1. Go to **Content** → **Promote it**
2. Find the article in the table
3. Drag it toward the bottom (higher weight)
4. Click **Save**

**Result**: Article remains promoted but appears after more important content.

### Workflow 4: Emergency Override

**Scenario**: You need to immediately feature breaking news.

**Steps**:
1. Edit the breaking news article
2. Check **"Promoted to front page"**
3. Set **Promotion weight** to `-100`
4. Save

**Result**: Content immediately appears at the top, even if someone else is managing other promoted content.

---

## Best Practices

### Content Organization

✅ **DO:**
- Use consistent weight ranges for different priority levels
- Document your weight strategy for your team
- Review promoted content regularly (weekly or monthly)
- Limit promoted content to 10-15 items for best performance

❌ **DON'T:**
- Use random weight values without a strategy
- Promote too much content (dilutes importance)
- Forget to unpromote outdated content
- Use the same weight for multiple items (causes unpredictable order)

### Weight Strategy

**Create a Weight Guide** for your editorial team:

```
Priority Level     | Weight Range | Example Content
-------------------|--------------|----------------------------------
Critical/Breaking  | -100 to -50  | Emergency announcements
High Priority      | -49 to -10   | Featured articles, campaigns
Normal Promoted    | -9 to 9      | Regular promoted content
Low Priority       | 10 to 50     | Secondary content
Archive Level      | 51 to 100    | Old but still promoted
```

### Team Workflows

If multiple editors manage promoted content:

1. **Assign Responsibilities**: One person manages each content type or section
2. **Communication**: Use editorial notes or Slack to coordinate
3. **Regular Reviews**: Schedule weekly promoted content review meetings
4. **Use Weight Ranges**: Each editor gets a weight range (avoid conflicts)

### Performance Tips

- **Limit promoted content**: 10-15 items is ideal
- **Unpromote old content**: Don't let the list grow indefinitely
- **Cache properly**: Use Views caching if displaying promoted content
- **Use Views filters**: Filter by date, content type, or taxonomy

---

## Troubleshooting

### Problem: Field Not Appearing on Content Type

**Symptoms**: The promotion weight field doesn't show on the node edit form.

**Solutions**:
1. Verify weighted promotion is enabled: **Structure** → **Content types** → [Type] → **Edit**
2. Clear all caches: `drush cr`
3. Check form display settings: **Structure** → **Content types** → [Type] → **Manage form display**
4. Verify field exists: `drush field:list`

### Problem: Changes Not Saving in Promote It Interface

**Symptoms**: Click "Save" but order doesn't change.

**Solutions**:
1. Check browser console for JavaScript errors (F12)
2. Verify you have "Manage promoted content order" permission
3. Clear browser cache and cookies
4. Try a different browser
5. Check Drupal error logs: **Reports** → **Recent log messages**

### Problem: Promoted Content Not Appearing on Front Page

**Symptoms**: Content is promoted but doesn't show on front page.

**Solutions**:
1. Verify content is actually promoted: Edit node, check "Promoted to front page"
2. Check your front page View configuration
3. Clear Views cache: `drush cc views`
4. Verify the View includes the promotion weight sort
5. Check if a custom front page is overriding the default

### Problem: Wrong Sort Order in Views

**Symptoms**: Promoted content appears in wrong order in Views.

**Solutions**:
1. Edit the View
2. Check **Sort Criteria**:
   - Should be "Promotion weight" **Ascending** (low to high)
   - Remove any conflicting sorts (e.g., Post date)
3. Clear Views cache
4. Resave all promoted nodes to refresh weight values

### Problem: Autocomplete Not Working

**Symptoms**: Can't search for content to add in Promote It interface.

**Solutions**:
1. Verify content exists and is **not already promoted**
2. Check that content type has weighted promotion enabled
3. Clear all caches
4. Verify autocomplete route is accessible: `/autocomplete/promote_it?q=test`
5. Check browser console for JavaScript errors

---

## Frequently Asked Questions

### General Questions

**Q: Can I use this module with any content type?**  
A: Yes! You can enable weighted promotion on any node-based content type (Article, Page, custom types, etc.).

**Q: Does this work with Paragraphs or Media?**  
A: Currently, only node entities are supported. Paragraphs and Media cannot be directly promoted.

**Q: Will this affect my SEO?**  
A: No. This module only affects content ordering, not URLs, metadata, or search engine visibility.

**Q: Can I promote the same content to multiple areas?**  
A: Yes. Use Views to create different displays of promoted content with different filters.

### Weight & Ordering

**Q: What happens if two items have the same weight?**  
A: Drupal will order them unpredictably (usually by node ID). Always use unique weights.

**Q: Can I use decimal weights like 5.5?**  
A: No. The field only accepts integers from -100 to 100.

**Q: Do I have to use all weight values?**  
A: No! Use whatever range makes sense. Many sites only use -10 to 10.

**Q: How do I reset all weights?**  
A: Go to **Content** → **Promote it**, manually set weights, or use a custom Drush script.

### Permissions & Access

**Q: Who can access the Promote It interface?**  
A: Only users with the "Manage promoted content order" permission.

**Q: Can editors set weights without this permission?**  
A: Yes! Editors with "Edit" permission can set weights directly on node edit forms.

**Q: Can I restrict promotion to certain roles?**  
A: Yes, via standard Drupal permissions for editing specific content types.

### Technical Questions

**Q: Is this compatible with Drupal 10 and 11?**  
A: Yes! The module supports Drupal 10.3+ and Drupal 11.

**Q: Does this work with JSON:API or REST?**  
A: Yes. The promotion weight field is exposed like any other field.

**Q: Can I export this to a multisite?**  
A: Yes, via Features or Configuration Management. Weight values are stored as field data.

**Q: How do I migrate weight data?**  
A: The weights are stored in the `field_promotion_weight` field. Use standard Drupal migration tools.

### Module Comparison

**Q: How is this different from the Weight module?**  
A: The Weight module provides generic weight functionality. Promote It is specifically designed for managing promoted content with a drag-and-drop interface.

**Q: How is this different from Draggable Views?**  
A: Draggable Views requires storing order in a separate table. Promote It uses a field, making it more portable and Views-compatible.

**Q: Can I use this with Flag module?**  
A: Yes! You can combine promoted content with Flag-based workflows.

---

## Getting Help

### Documentation
- **README**: [README.md](README.md) - Installation and overview
- **API Documentation**: [API.md](API.md) - For developers
- **This Guide**: Step-by-step instructions

### Support Resources
- **Drupal.org Issue Queue**: [Link when available]
- **Drupal Slack**: #contribute or #support channels
- **Community Forums**: Post on drupal.org/forum

### Reporting Issues
If you encounter a bug:
1. Check existing issues in the issue queue
2. Provide steps to reproduce
3. Include Drupal version, PHP version, and browser
4. Add screenshots if applicable
5. Check error logs for relevant messages

---

## Appendix: Glossary

**Promoted Content**: Content marked to appear on the front page (or other special listings)

**Weight**: A numeric value (-100 to 100) that determines sort order

**Field Storage**: The database structure storing field data (shared across bundles)

**Field Instance**: The configuration of a field on a specific bundle

**Bundle**: A subtype of an entity (e.g., "Article" is a bundle of the "Node" entity type)

**Drag Handle**: The icon (⠿) used to reorder rows via drag-and-drop

**Autocomplete**: A search field that suggests results as you type

---

*Last Updated: February 2026*
