#include < amxmodx >
#include < regex >
#include < sqlx >


new const VERSION[] = "1.0";

new const host[] = "127.0.0.1";
new const user[] = "root";
new const pass[] = "";
new const db[]   = "mysql_dust";

new Handle:tuple;

enum _:eActions
{
    KICK,
    BAN,
    HIDE,
    WHITELIST,
    REPLACE
}

new Array:szWhitePatterns;
new Array:szPatterns;
new Array:iBlock;
new Array:iTime;
new Array:szReason;

new Array:szReplace;
new Array:szReplaceWith;

new bool:hasFinished;
new pBanPattern;
new pSaveLogs;

public plugin_init()
{
    register_plugin( "Chat Manager", VERSION, "DusT" );

    register_cvar( "AmX_DusT", "Chat_Manager", FCVAR_SPONLY | FCVAR_SERVER );

    pBanPattern = register_cvar( "cm_ban_pattern", "amx_ban [user] [time] [reason]" );
    pSaveLogs   = register_cvar( "cm_logs", "0" );
    register_clcmd( "say", "CmdCheckSay" );
    register_clcmd( "say_team", "CmdCheckSay" );
    register_clcmd( "amx_tsay", "CmdCheckSay" );
    register_clcmd( "amx_csay", "CmdCheckSay" );
    register_clcmd( "amx_say" , "CmdCheckSay" );
    register_clcmd( "amx_chat", "CmdCheckSay" );
    register_clcmd( "amx_csay", "CmdCheckSay" );
    register_clcmd( "amx_psay", "CmdCheckSay" );

    register_message( get_user_msgid( "SayText" ), "CmdSayText" );
    //register_event( "SayText", "CmdSayText", "b" );
    tuple = SQL_MakeDbTuple( host, user, pass, db );
}

public CmdSayText( msgId, msgDest, msgEnt )
{
    if( is_user_connected( msgEnt ) )
    {
        new szMessage[ 192 ];
        get_msg_arg_string( 4, szMessage, charsmax( szMessage ) );
        new max = ArraySize( szReplace );
        new Regex:rPattern;
        new subr[ 64 ];
        new bool:hasChanged = false;
        for( new i, pattern[ 64 ]; i < max; i++ )
        {
            ArrayGetString( szReplace, i, pattern, charsmax( pattern ) );

            rPattern = regex_match( szMessage, pattern );
                
            while( _:rPattern > 0 )
            {
                hasChanged = true;
                regex_substr( rPattern, 0, subr, charsmax( subr ) );
                replace( szMessage, charsmax( szMessage ), subr, fmt( "%a", ArrayGetStringHandle( szReplaceWith, i ) ) );
                regex_free( rPattern );
                rPattern = regex_match( szMessage, pattern );
            }
        }
        //regex_free( rPattern );
        if( hasChanged )
            set_msg_arg_string( 4, szMessage );
    }
}

public plugin_end()
{
    ArrayDestroy( szPatterns );
    ArrayDestroy( szWhitePatterns );
    ArrayDestroy( iBlock );
    ArrayDestroy( iTime );
    ArrayDestroy( szReason );
    ArrayDestroy( szReplace );
    ArrayDestroy( szReplaceWith );
}
public plugin_cfg()
{
    set_task( 0.1, "SQL_Init" );
    szPatterns = ArrayCreate( 128, 1 );
    szWhitePatterns = ArrayCreate( 128, 1 );
    iBlock = ArrayCreate( 1, 1 );
    iTime = ArrayCreate( 1, 1 );
    szReason = ArrayCreate( 64, 1 );
    szReplace = ArrayCreate( 128, 1 );
    szReplaceWith = ArrayCreate( 64, 1 );
}

public SQL_Init()
{
    new query[ 512 ];

    formatex( query, charsmax( query ), "\
    CREATE TABLE IF NOT EXISTS `db_patterns`\
    ( id INT NOT NULL AUTO_INCREMENT, pattern VARCHAR(128) NOT NULL, block_type INT NOT NULL, time INT, reason VARCHAR(64), PRIMARY KEY( id ) );" );

    SQL_ThreadQuery( tuple, "IgnoreHandle", query );

    formatex( query, charsmax( query ), "SELECT * FROM `db_patterns`" );

    SQL_ThreadQuery( tuple, "SQL_LoadData", query );
}

public IgnoreHandle( failState, Handle:query, error[], errNum )
{
    if( errNum )
        set_fail_state( error );

    SQL_FreeHandle( query );
}

public SQL_LoadData( failState, Handle:query, error[], errNum )
{
    if( errNum )
        set_fail_state( error );

    new max = SQL_NumResults( query );
    new blockType, time, pattern[ 128 ], reason[ 64 ];

    for( new i; i < max; i++ )
    {
        SQL_ReadResult( query, 1, pattern, charsmax( pattern ) );
        blockType = SQL_ReadResult( query, 2 );

        if( blockType == REPLACE ) 
        {
            SQL_ReadResult( query, 1, pattern, charsmax( pattern ) );
            ArrayPushString( szReplace, pattern );
            SQL_ReadResult( query, 4, reason, charsmax( reason ) );
            ArrayPushString( szReplaceWith, reason );
        }
        else if( blockType == WHITELIST )
        {
            SQL_ReadResult( query, 1, pattern, charsmax( pattern ) );
            ArrayPushString( szWhitePatterns, pattern );
        }
        else
        {
            ArrayPushCell( iBlock, blockType )
            ArrayPushString( szPatterns, pattern );
            if( blockType != HIDE )
            {
                SQL_ReadResult( query, 4, reason, charsmax( reason ) );
                time = SQL_ReadResult( query, 3 );
            }
            else
            {
                time = 0;
                reason[ 0 ] = 0;
            }
            ArrayPushCell( iTime, time );
            ArrayPushString( szReason, reason );
        }
        SQL_NextRow( query );
    }
    hasFinished = true;
}

public CmdCheckSay( id )
{
    if( !hasFinished )
        return PLUGIN_CONTINUE;

    new argv[ 32 ];
    new sayType;

    read_argv( 0, argv, charsmax( argv ) );

    if( equali( argv, "amx_psay" ) )
        sayType = 1;
    else if( !equali( argv, "say" ) )
        sayType = 2;
    
    new args[ 192 ];

    read_args( args, charsmax( args ) );
    remove_quotes( args );

    if( args[ 0 ] == '@' )
        sayType = 2;

    if( sayType == 1 )
    {
        read_argv( 1, argv, charsmax( argv ) );
        format( args, charsmax( args ), "%s", args[ strlen( argv ) + 1 ] );
    }

    new max = ArraySize( szWhitePatterns );
    new pattern[ 128 ];
    new i;
    new Regex:rPattern;
    for( i = 0; i < max; i++ )
    {
        ArrayGetString( szWhitePatterns, i, pattern, charsmax( pattern ) );

        if( _:( rPattern = regex_match( args, pattern ) ) > 0 )
        {
            regex_free( rPattern );
            return PLUGIN_CONTINUE;
        }
    }

    max = ArraySize( szPatterns );

    new time, blockType, reason[ 64 ];
    
    for( i = 0; i < max; i++ )
    {
        ArrayGetString( szPatterns, i, pattern, charsmax( pattern ) );

        if( _:( rPattern = regex_match( args, pattern ) ) > 0 )
        {
            blockType = ArrayGetCell( iBlock, i );
            regex_free( rPattern );
            if( blockType != HIDE )
            {
                time = ArrayGetCell( iTime, i );
                ArrayGetString( szReason, i, reason, charsmax( reason ) );

                if( blockType == KICK )
                    server_cmd( "kick #%d %s", get_user_userid( id ), reason ); 
                else if( blockType == BAN )
                {
                    new banPattern[ 128 ];
                    get_pcvar_string( pBanPattern, banPattern, charsmax( banPattern ) );

                    replace_all( banPattern, charsmax( banPattern ), "[user]", fmt( "#%d", get_user_userid( id ) ) );
                    replace_all( banPattern, charsmax( banPattern ), "[reason]", reason );
                    replace_all( banPattern, charsmax( banPattern ), "[time]", fmt( "%d", time ) );

                    client_print_color( id, print_team_red, "^3[CM]^1 ^4Chat Triggered:^1 %s", args );
                    server_cmd( banPattern );
                }
            }
            if( get_pcvar_bool( pSaveLogs ) )
                log_amx( "%N Triggered. Chat: [%s], Pattern: [%s]", id, args, pattern );

            if( !sayType )
                return PLUGIN_HANDLED_MAIN;
            else
                return PLUGIN_HANDLED;
        }
    }
    return PLUGIN_CONTINUE;
}
