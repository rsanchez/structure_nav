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
{<depth>:site_id}
{<depth>:channel_id}
{<depth>:author_id}
{<depth>:forum_topic_id}
{<depth>:ip_address}
{<depth>:url_title}
{<depth>:status}
{<depth>:versioning_enabled}
{<depth>:view_count_one}
{<depth>:view_count_two}
{<depth>:view_count_three}
{<depth>:view_count_four}
{<depth>:allow_comments}
{<depth>:sticky}
{<depth>:entry_date}
{<depth>:year}
{<depth>:month}
{<depth>:day}
{<depth>:expiration_date}
{<depth>:comment_expiration_date}
{<depth>:edit_date}
{<depth>:recent_comment_date}
{<depth>:comment_total}
{<depth>:channel}
{<depth>:channel_short_name}
{<dept>:your_custom_field}
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