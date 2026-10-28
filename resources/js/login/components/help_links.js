import React, { useMemo } from "react";
import Link from "@material-ui/core/Link";
import styles from "../login.module.scss";

const HelpLinks = ({
  userName,
  showEmitOtpAction,
  forgotPasswordAction,
  showForgotPasswordAction,
  showVerifyEmailAction,
  verifyEmailAction,
  showHelpAction,
  helpAction,
  appName,
  emitOtpAction,
}) => {
  const actions = useMemo(() => {
    let forgotPasswordActionHref = forgotPasswordAction;
    if (userName) {
      const separator = forgotPasswordAction.includes("?") ? "&" : "?";
      forgotPasswordActionHref = `${forgotPasswordAction}${separator}email=${encodeURIComponent(userName)}`;
    }

    return [
      {
        show: showEmitOtpAction,
        href: "#",
        onClick: emitOtpAction,
        label: "Get A Single-use Code emailed to you",
      },
      {
        show: showForgotPasswordAction,
        href: forgotPasswordActionHref,
        label: "Reset your password",
      },
      {
        show: showVerifyEmailAction,
        href: verifyEmailAction,
        label: `Verify ${appName}`,
      },
      {
        show: showHelpAction,
        href: helpAction,
        label: "Having trouble?",
      },
    ].filter((action) => action.show);
  }, [
    showEmitOtpAction,
    showForgotPasswordAction,
    showVerifyEmailAction,
    showHelpAction,
    userName,
    forgotPasswordAction,
    verifyEmailAction,
    helpAction,
    appName,
    emitOtpAction,
  ]);

  return (
    <>
      <hr className={styles.separator} />
      {actions.map((action, index) => (
        <Link
          key={index}
          href={action.href}
          onClick={action.onClick}
          variant="body2"
          target="_self"
        >
          {action.label}
        </Link>
      ))}
    </>
  );
};

export default HelpLinks;
