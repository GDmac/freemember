###########
Members Tag
###########
::

  {exp:freemember:members}

The Members tag allows you to search for and display information about your site's members.
This includes member list pages, as well as individual member profile pages.

.. contents::
  :local:

**********************
Members Tag Parameters
**********************

member_id
=========
Specify a member ID to display. You can use this in combination with a segment variable to display
member profiles::

    {exp:freemember:members member_id="{segment_3}"}

You can also specify multiple member IDs by separating them with pipes::

    {exp:freemember:members member_id="1|2|3"}
    {exp:freemember:members member_id="not 5|6"}

username
========
Specify a username to display. You can use this in combination with a segment variable to display
member profiles::

    {exp:freemember:members username="{segment_3}"}

group_id
========
List members based on their member group.

You can also specify multiple group IDs by separating them with pipes::

    {exp:freemember:members group_id="1|2|3"}
    {exp:freemember:members group_id="not 5|6"}

limit
=====
Limit the number of members found. Defaults to 50.

offset
======
Specify the result offset. Default to 0. For example, to display the third page of 10 results,
you would use the following parameters::

    {exp:freemember:members limit="10" offset="20"}

orderby
=======
Specify which column to sort the members on. You can also use custom member fields here.
Defaults to ``member_id``.

sort
====
Specify which direction to sort the result in. Must be one of ``asc`` or ``desc``.
Defaults to ``asc``.

*********************
Members Tag Variables
*********************

member_id
=========
The member's ID.

username
========
The member's username.

email
=====
The member's email address.

screen_name
===========
The member's screen name.

url
===
The member's URL.

location
========
The member's location.

occupation
==========
The member's occupation.

interests
=========
The member's interests.

bio
===
The member's bio.

signature
=========
The member's signature.

bday_d
======
The member's birthday day.

bday_m
======
The member's birthday month.

bday_y
======
The member's birthday year.

Custom Member Fields
====================
All custom member fields are available, using the syntax ``{field_name}``.

*******************
Members Tag Example
*******************
::

    <h2>Website Admins</h2>

    {exp:freemember:members group_id="1"}

        <p>{username}: {email}</p>

    {/exp:freemember:members}
