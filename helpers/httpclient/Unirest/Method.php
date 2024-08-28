<?php

/**
 * Hypertext Transfer Protocol (HTTP) Method Registry
 *
 * http://www.iana.org/assignments/http-methods/http-methods.xhtml
 */

namespace Unirest;

interface Method
{
    // RFC7231
    public const GET = 'GET';
    public const HEAD = 'HEAD';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const CONNECT = 'CONNECT';
    public const OPTIONS = 'OPTIONS';
    public const TRACE = 'TRACE';

    // RFC3253
    public const BASELINE = 'BASELINE';

    // RFC2068
    public const LINK = 'LINK';
    public const UNLINK = 'UNLINK';

    // RFC3253
    public const MERGE = 'MERGE';
    public const BASELINECONTROL = 'BASELINE-CONTROL';
    public const MKACTIVITY = 'MKACTIVITY';
    public const VERSIONCONTROL = 'VERSION-CONTROL';
    public const REPORT = 'REPORT';
    public const CHECKOUT = 'CHECKOUT';
    public const CHECKIN = 'CHECKIN';
    public const UNCHECKOUT = 'UNCHECKOUT';
    public const MKWORKSPACE = 'MKWORKSPACE';
    public const UPDATE = 'UPDATE';
    public const LABEL = 'LABEL';

    // RFC3648
    public const ORDERPATCH = 'ORDERPATCH';

    // RFC3744
    public const ACL = 'ACL';

    // RFC4437
    public const MKREDIRECTREF = 'MKREDIRECTREF';
    public const UPDATEREDIRECTREF = 'UPDATEREDIRECTREF';

    // RFC4791
    public const MKCALENDAR = 'MKCALENDAR';

    // RFC4918
    public const PROPFIND = 'PROPFIND';
    public const LOCK = 'LOCK';
    public const UNLOCK = 'UNLOCK';
    public const PROPPATCH = 'PROPPATCH';
    public const MKCOL = 'MKCOL';
    public const COPY = 'COPY';
    public const MOVE = 'MOVE';

    // RFC5323
    public const SEARCH = 'SEARCH';

    // RFC5789
    public const PATCH = 'PATCH';

    // RFC5842
    public const BIND = 'BIND';
    public const UNBIND = 'UNBIND';
    public const REBIND = 'REBIND';
}
