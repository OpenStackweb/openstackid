import React, {useEffect, useState, Fragment} from "react";
import {Box, Checkbox, FormControlLabel, Grid, Tooltip, Typography} from "@material-ui/core";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";

const AllowedScopesPanel = ({scopes, selectedScopes, onScopeSelected, onScopeUnselected}) => {
    const [apisInfo, setApisInfo] = useState([]);

    useEffect(() => {
        //get api info from scopes
        setApisInfo([...new Map(scopes.map(scope => [scope['api_name'], scope])).values()]);
    }, []);

    const handleCheckboxChange = (e) => {
        let {checked, id} = e.target;
        id = parseInt(id);
        if (checked && onScopeSelected) {
            onScopeSelected(id)
        }
        if (!checked && onScopeUnselected) {
            onScopeUnselected(id)
        }
    }

    return (
        <Grid
            container
            direction="column"
            spacing={2}
            justifyContent="center"
        >
            {
                apisInfo.map(apiInfo => (
                    <Fragment key={apiInfo.api_name}>
                        <Grid item container direction="row" alignItems="center">
                            <Box
                                component="img"
                                sx={{
                                    height: 20,
                                    width: 20
                                }}
                                alt={apiInfo.api_name}
                                src={apiInfo.api_logo}
                            />
                            &nbsp;
                            <Typography variant="h6" display="inline">{apiInfo.api_name}</Typography>
                            &nbsp;
                            <Tooltip title={apiInfo.api_description}>
                                <InfoOutlinedIcon fontSize="small"/>
                            </Tooltip>
                        </Grid>
                        <Grid item container direction="row" alignItems="center">
                            {
                                scopes
                                    .filter(scope => scope.api_name === apiInfo.api_name)
                                    .map(scope => <FormControlLabel
                                        id={scope.id.toString()}
                                        name={scope.name}
                                        key={scope.id.toString()}
                                        control={<Checkbox
                                            color="primary"
                                            id={scope.id.toString()}
                                            checked={selectedScopes.includes(scope.id)}
                                            onChange={handleCheckboxChange}
                                        />}
                                        label={<>
                                            <Typography display="inline">{scope.name}</Typography>
                                            <Tooltip title={scope.description}>
                                                <InfoOutlinedIcon fontSize="small"/>
                                            </Tooltip>
                                        </>}
                                        labelPlacement="end"
                                    />)
                            }
                        </Grid>
                    </Fragment>)
                )
            }
        </Grid>
    );
}

export default AllowedScopesPanel;