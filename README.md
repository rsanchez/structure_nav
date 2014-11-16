# Structure Nav

ExpressionEngine plugin for making custom Structure navigation using a tag pair.

## Parameters

Same parameters as [`{exp:structure:nav}`](http://buildwithstructure.com/tags#tag_navigation), except those which effect HTML or CSS class/id.

## Variables

All tags are prefixed based on their depth. The tag prefixes go in this order:

```
root
child
grandchild
great_grandchild
great_great_grandchild
etc.
```

The [`basic`](#basic-example) tag has these variables (where <prefix> is one of the depth levels listed above)

```
{<prefix>:entry_id}
{<prefix>:title}
{<prefix>:page_url}
{<prefix>:page_uri}
{<prefix>:count}
{<prefix>:total_results}
{if <prefix>:active}{/if}
{if <prefix>:has_active_child}{/if}
{if <prefix>:has_children}
```

There is also the `children` tag pair:

```
{<prefix>:children}
  {<next prefix>:entry_id}
  {<next prefix>:title}
  {<next prefix>:children}
    {<next next prefix>:entry_id}
  {/<next prefix>:children}
{/<prefix>:children}
```

Which would look like this with real prefixes:

```
{root:children}
  {child:entry_id}
  {child:title}
  {child:children}
    {grandchild:entry_id}
  {/child:children}
{/root:children}
```

The [`advanced`](#advanced-example) tag adds the following tags and also any custom fields:

```
{<prefix>:site_id}
{<prefix>:channel_id}
{<prefix>:author_id}
{<prefix>:forum_topic_id}
{<prefix>:ip_address}
{<prefix>:url_title}
{<prefix>:status}
{<prefix>:versioning_enabled}
{<prefix>:view_count_one}
{<prefix>:view_count_two}
{<prefix>:view_count_three}
{<prefix>:view_count_four}
{<prefix>:allow_comments}
{<prefix>:sticky}
{<prefix>:entry_date}
{<prefix>:year}
{<prefix>:month}
{<prefix>:day}
{<prefix>:expiration_date}
{<prefix>:comment_expiration_date}
{<prefix>:edit_date}
{<prefix>:recent_comment_date}
{<prefix>:comment_total}
{<prefix>:channel}
{<prefix>:channel_short_name}
{<prefix>:your_custom_field}
```

## Basic Example

```
{exp:structure_nav:basic show_depth="4"}
{if root:count == 1}
<ul>
{/if}
  <li{if root:active} class="active"{/if}>
    <a href="{root:page_url}">{root:title}</a>
    {if root:has_children}
    <ul>
      {root:children}
      <li{if child:active} class="active"{/if}>
        <a href="{child:page_url}">{child:title}</a>
        {if child:has_children}
        <ul>
          {child:children}
          <li{if grandchild:active} class="active"{/if}>
            <a href="{grandchild:page_url}">{grandchild:title}</a>
            {if grandchild:has_children}
            <ul>
              {grandchild:children}
              <li{if great_grandchild:active} class="active"{/if}>
                <a href="{great_grandchild:page_url}">{great_grandchild:title}</a>
              </li>
              {/grandchild:children}
            </ul>
            {/if}
          </li>
          {/child:children}
        </ul>
        {/if}
      </li>
      {/root:children}
    </ul>
    {/if}
  </li>
{if root:count == root:total_results}
</ul>
{/if}
{/exp:structure_nav:basic}
```

## Advanced Example

```
{exp:structure_nav:advanced show_depth="4"}
{if root:count == 1}
<ul>
{/if}
  <li{if root:active} class="active"{/if}>
    <a href="{root:page_url}">{root:title} {root:entry_date format="%Y-%m-%d %H:%i:%s"} ({root:status})</a>
    <div class="root-info">
      <p>{root:your_custom_field}</p>
      <ul>
      {root:your_custom_grid_field}
        <li>{your_col}</li>
      {/root:your_custom_grid_field}
      </ul>
    </div>
    {if root:has_children}
    <ul>
      {root:children}
      <li{if child:active} class="active"{/if}>
        <a href="{child:page_url}">{child:title} ({child:status})</a>
        {if child:has_children}
        <ul>
          {child:children}
          <li{if grandchild:active} class="active"{/if}>
            <a href="{grandchild:page_url}">{grandchild:title} ({grandchild:status})</a>
          </li>
          {/child:children}
        </ul>
        {/if}
      </li>
      {/root:children}
    </ul>
    {/if}
  </li>
{if root:count == root:total_results}
</ul>
{/if}
{/exp:structure_nav:advanced}
```