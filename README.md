
What is this?
=============

This is a cache store purely for developement which is used to highlight
various bugs in code which uses a versioned cache, in particular to test
the new mod info partial course rebuild.

In generally it is a mostly functional cache which stores things in files.
The things it does differently is:

1) It never deletes data. In fact if you all the $cache->delete method it
   throws an exception. If you are using versioned caches you should never
   delete anything, you should only ever add a new version and keep the
   cache constantly warm to avoid cache stampedes.

2) It stores everything under a snapshot, and the snapshot can be easily
   bumped manually by:

   php admin/cli/cfg.php --component=cachestore_filediff --name=snapshot --set=2

   This snapshot is also bumped when the cache definition is purged.

3) It stores extra data in the cache file like the stack trace and a nicer
   json version of the data to make it very easy to debug, and specifically
   make its easier to do a normal diff.

The point of this is to highlight two scenarios really clearly:

a) If you are making a small targeted change, lets say you are toggling
   the visibility of a module and this changes various things and then
   invalidates some caches. We want to take a snapshot of before the change
   is made, after the change is made, and then empty the cache and let it
   refill for a 3rd state. We expect that state 2 and state 3 are EXACTLY
   the same. All version numbers and time stamps and everything should be
   binary identical. If they are not then there is a bug somewhere.

```
# Reset all snapshots:
php public/cache/stores/filediff/cli/reset.php

# Load a course...

cd /var/lib/sitedata/filediff/core_coursemodinfo
# Find files under /1/

# Make a new snapshot:
php public/cache/stores/filediff/cli/snapshot.php

# Load a course...
# Find files under /2/

# Recursive diff of snapshots:
diff -r --color=always --ignore-matching-lines=version_wrapper *
```

b) On the flip side, if you are doing a small change like above, and then
   invalidating too much then while it may be correct it is also inneficient.
   So we want to easily be able to see any cache values which have been
   set when they should not have been set. So in this case we want to clone
   the cache so its still warm, and then change something.

TBA create clone cli




