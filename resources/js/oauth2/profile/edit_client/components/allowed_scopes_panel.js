import React, {useEffect, useState, Fragment} from "react";
import {Box, Checkbox, FormControlLabel, Grid, Tooltip, Typography} from "@material-ui/core";
import InfoOutlinedIcon from "@material-ui/icons/InfoOutlined";

const AllowedScopesPanel = ({scopes, selectedScopes, onScopeSelected, onScopeUnselected}) => {
    const [apisInfo, setApisInfo] = useState([]);
    const selectedScopeIds = selectedScopes.map(scope => scope.id);

    useEffect(() => {
        //get api info from scopes
        setApisInfo([...new Map(scopes.map(scope => [scope.api.name, scope])).values()]);
    }, []);

    const handleCheckboxChange = (e) => {
        let {checked, id, name} = e.target;
        id = parseInt(id);
        if (checked && onScopeSelected) {
            onScopeSelected(id, name)
        }
        if (!checked && onScopeUnselected) {
            onScopeUnselected(id, name)
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
                    <Fragment key={apiInfo.api.name}>
                        <Grid item container direction="row" alignItems="center">
                            <Box
                                component="img"
                                sx={{
                                    height: 20,
                                    width: 20
                                }}
                                alt={apiInfo.api.name}
                                src={apiInfo.api.logo}
                            />
                            &nbsp;
                            <Typography variant="h6" display="inline">{apiInfo.api.name}</Typography>
                            &nbsp;
                            <Tooltip title={apiInfo.api.description}>
                                <InfoOutlinedIcon fontSize="small"/>
                            </Tooltip>
                        </Grid>
                        <Grid item container direction="row" alignItems="center">
                            {
                                scopes
                                    .filter(scope => scope.api.name === apiInfo.api.name)
                                    .map(scope => <FormControlLabel
                                        id={scope.id.toString()}
                                        name={scope.name}
                                        key={scope.id.toString()}
                                        control={<Checkbox
                                            color="primary"
                                            id={scope.id.toString()}
                                            checked={selectedScopeIds.includes(scope.id)}
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