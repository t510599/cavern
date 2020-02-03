# Cavern API Reference

## Table of Content
- [Login](#Login)
- [User](#User)
    - [Constants](#Constants)
        - [Role](#Role)
    - [Types](#Types)
        - [User](#User-1)
        - [CurrentUser](#CurrentUser)
    - [Errors](#Errors)
        - [NoUserError](#NoUserError)
    - [Methods](#Methods)
        - [get](#get)
        - [get_with_username](#get_with_username)
- [Post](#Post)
- [Like](#Like)
- [Comment](#Comment)
- [Notification](#Notification)


### Login
### User
### Constants
#### Role
The role of a user represent the permission given to the user.

A user's role is usually sent as a number in the response. However, we suggest using displayed name when it is shown to user.

Level | Displayed Name | View post | Comment | Like | Receive notification | Create new posts | Edit posts | Delete posts | 
------ | ------------------- | ------------ | ----------- | ----- | ---------------------- | -------------------- | ------------| -------------
0       | 會員                    | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ 
1       | 作者                    | ✅ | ✅ | ✅ | ✅ | ✅ | him/herself | him/herself | him/herself |
8       | 管理員                | ✅ | ✅ | ✅ | ✅ | ✅ | all | all | all
9       | 站長                    | ✅ | ✅ | ✅ | ✅ | ✅ | all | all | all 

### Types
#### User
Contains information about a user.

<table>
<tr>
    <th>Json representation</th>
</tr>
<tr>
<td>

```json
{
    "username": string,
    "name": string,
    "level": number,
    "role": string,
    "hash": string,
    "muted": boolean,
    "posts_count": number,
    "login": boolean,
    "fetch": number
}
```
</td>
</tr>
</table>

<table>
<tr>
<th colspan="2">Fields</th>
</tr>

<tr>
<td>username</td>
<td>The user's username.</td>
</tr>

<tr>
<td>name</td>
<td>The user's displayed name.</td>
</tr>

<tr>
<td>level</td>
<td>The user's role. See <a href="#Role">Role's section</a>.</td>
</tr>

<tr>
<td>role</td>
<td>The displayed name of the user's role. It will always be compatible with the level number. </td>
</tr>

<tr>
<td>hash</td>
<td>Hashed value of the user's email. Call <pre>https://www.gravatar.com/avatar/{hash}?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=500</pre> to get user's avatar.</td>
</tr>

<tr>
<td>muted</td>
<td>Indicate whether the user can create new post, edit post, and comment.</td>
</tr>

<tr>
<td>posts_count</td>
<td>The number of posts which was created by the user.</td>
</tr>

<tr>
<td>login</td>
<td>Indicate whether <b>the request sender</b> is logged in. </td>
</tr>

<tr>
<td>fetch</td>
<td>Fetch series of the request.</td>
</tr>
</table>

#### CurrentUser
Contains information about the current user, which has logged in on the client side.

This is a extended version of [User](#User-1) which contains more information.

<table>
<tr>
    <th>Json representation</th>
</tr>
<tr>
<td>

```json
{
    "username": string,
    "name": string,
    "level": number,
    "role": string,
    "hash": string,
    "muted": boolean,
    "posts_count": number,
    "email": string,
    "login": boolean,
    "fetch": number
}
```
</td>
</tr>
</table>

<table>
<tr>
<th colspan="2">Fields</th>
</tr>

<tr>
<td>username</td>
<td>The current user's username.</td>
</tr>

<tr>
<td>name</td>
<td>The current user's displayed name.</td>
</tr>

<tr>
<td>level</td>
<td>The current user's role. See <a href="#Role">Role's section</a>.</td>
</tr>

<tr>
<td>role</td>
<td>The displayed name of the current user's role. It will always be compatible with the level number. </td>
</tr>

<tr>
<td>hash</td>
<td>Hashed value of the current user's email. Call <pre>https://www.gravatar.com/avatar/{hash}?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=500</pre> to get user's avatar.</td>
</tr>

<tr>
<td>muted</td>
<td>Indicate whether the current user can create new post, edit post, and comment.</td>
</tr>

<tr>
<td>posts_count</td>
<td>The number of posts which was created by the  current user.</td>
</tr>

<tr>
<td>email</td>
<td>The email of the current user.</td>
</tr>

<tr>
<td>login</td>
<td>It should always be true. Since there's no current user if there's no user logged in on the client side.</td>
</tr>

<tr>
<td>fetch</td>
<td>Fetch series of the request.</td>
</tr>
</table>

### Errors
#### NoUserError
Get when the specific username you queried is not available.
<table>
<tr>
<th>Json Representation</th>
</tr>
<tr>
<td>

```json
{
    "status":"nouser",
    "login": boolean,
    "fetch": number
}
```
</td>
</tr>
</table>

<table>
<tr>
<th colspan="2">Fields</th>
</tr>

<tr>
<td>status</td>
<td>The error message. In this case, it is always "nouser".</td>
</tr>

<tr>
<td>login</td>
<td>Indicate whether <b>the request sender</b> is logged in.</td>
</tr>

<tr>
<td>fetch</td>
<td>Fetch series of the request.</td>
</tr>
</table>

### Methods

#### get
**HTTP request**

```GET /ajax/user.php```

**Response body**

Developer can call this method **if and only if** the client is logged in. 
The server will return a [CurrentUser](#CurrentUser). 

However, if the client isn't logged in,  the server will send out a 404 error.

#### get_with_username
**HTTP requeset**

```GET /ajax/user.php?username={username}```
<table>
<tr>
<th colspan="2">Paramaters</th>
</tr>
<tr>
<td valign="top">username</td>
<td valign="top">string<br /> The username of the user who you want to query.</td>
</tr>
</table>

**Response body**

If **the request sender** is logged in, and the username of the current user is the same as the queried username, the server will return a [CurrentUser](#CurrentUser). Otherwise, a [User](#User-1) will be returned.

### Post
#### Constants
### Like
### Comment
### Notification
