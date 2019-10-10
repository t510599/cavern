# Cavern
> Explore those deep inside the cave.  

A simple blog system.

## Feature
Able to make comments and like posts
Distributes users onto different classes, each had its own permissions
## Requirements
- `php-mbstring`

## Install
1. Set database information. `connection/SQL.php`
2. Set blog config. `config.php`
3. Done!

## Libraries
editormd

## Cavern API Quick Start
To get data from Cavern, a recommended way is to use the **Cavern API**.

Following are 2 commonly used api endpoints to create a viewer for the Cavern website.

- [Posts](#Posts)
- [User Profile](#User-Profile)

To see full documentation, please visit [reference](reference.md).

### Posts
#### Posts list
In order to get posts, the following endpoint is used
```
/ajax/posts.php
```
The reponse of the api call contains 
- fetch series(```fetch```)
- number of posts(```page_limit```)
- current page(```page```)
- number of all posts(```all_posts_count```)
- posts list(```posts```)

and each post inside posts list contains
- author's username(```author```)
- author's displayed name(or nickname)(```name```)
- the PID of the post(```pid```)
- the title of the post(```title```)
- the time when the post is sent(```time```)
- the number of likes the post received(```likes_count```)
- the number of comments about the post(```comments_count```)
- whether the current user has liked it(*note: if it isn't logged, the result will be false*)(```islike```)

```json
{
    "fetch":1570115846126,
    "page_limit":1,
    "page":3,
    "all_posts_count":200,
    "posts":
    [
        {
            "author":"ExampleAuthor", 
            "name":"AuthorName",
            "pid":300,
            "title":"ExampleTitle",
            "time":"2019-08-29 13:00:00",
            "likes_count":"1",
            "comments_count":"3",
            "islike":false
            
        }
    ]
}
```
To change the number of posts you want to receive or current page, you can specify the  ```limit``` and ```page``` arguments.

```
/ajax/posts.php?limit={number of posts}&&page={page number}
```

#### Post Content of a Specific PID
To get an post's content, you must access it with its PID.
```
/ajax/posts.php?pid={PID}
```
The response contains 
- fetch series(```fetch```)
- post content(```post```)

The body of post contains
- author's username(```author```)
- author's diaplyed name(or nickname)(```name```)
- the title of the post(```title```)
- the content of the post(```content```)
- the time when the post is sent(```time```)
- the number of likes the post received(```likes_count```)
- the number of comments about the post(```comments_count```)
- whether the current user has liked it(*note: if it isn't logged in, the result will be false*)(```islike```)

```json
{
    "fetch":1570117194098,
    "post":
    {
        "author":"ExampleAuthor",
        "name":"ExampleAuthorName",
        "title":"ExampleTitle",
        "content":"ExampleContent",
        "time":"2019-06-01 12:00:00",
        "likes_count":"0",
        "comments_count":"10",
        "islike":false
    }
}
```

### User Profile

#### User's level
In Cavern API, we use different level number to represent the ability to access resources and functionality. 
The following table is the level numbers and its permissions.

Level | Displayed Name | View post | Comment | Like | Receive notification | Create new posts | Edit posts | Delete posts | 
------ | ------------------- | ------------ | ----------- | ----- | ---------------------- | -------------------- | ------------| -------------
0       | 會員                    | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ 
1       | 作者                    | ✅ | ✅ | ✅ | ✅ | ✅ | him/herself | him/herself | him/herself |
8       | 管理員                | ✅ | ✅ | ✅ | ✅ | ✅ | all | all | all
9       | 站長                    | ✅ | ✅ | ✅ | ✅ | ✅ | all | all | all 

#### Get User's Profile with a Specific Username
Use the following endpoint to get a user's profile with a specific username.
```
/ajax/user.php?username={username}
```
The response contains
- the username(```username```)
- the displayed name(```name```)
- user's level number(```level```)
- displayed name of the user's level number(*note: in unicode*)(```role```)
- the hash value of the user's email(*for surcurity reason, Cavern API won't provide developers with user's email*)(```hash```)
- whether the user is muted(```muted```)
- the number of posts the user created(```posts_count```)
- whether **you** are in a logged in state(```login```)
- fetch series(```fetch```)

```json
{
    "username":"ExampleUsername",
    "name":"ExampleName",
    "level":8,
    "role":"\u7ba1\u7406\u54e1",
    "hash":"32c018e02c34cf3fddfbebba6b44c5fc",
    "muted":false,
    "posts_count":25,
    "login":false,
    "fetch":1570285748411
}
```

Again, to see full documentation, please visit our [reference](reference.md)
