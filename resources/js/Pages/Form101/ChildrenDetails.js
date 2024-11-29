//ChildrenDetails

import React from "react";
import TextField from "./inputElements/TextField";
import DateField from "./inputElements/DateField";
import CheckBox from "./inputElements/CheckBox";
import { useTranslation } from "react-i18next";
import { FaRegTrashCan } from "react-icons/fa6";


export const childrenInitial = {
    firstName: "",
    IdNumber: "",
    Dob: "",
    inCustody: false,
    haveChildAllowance: false,
};
const ChildrenDetails = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble
}) => {
    const { t } = useTranslation();

    return (
        <div>
            <p className="navyblueColor font-24 font-w-500  mb-2">{t("form101.children_headline")}</p>

            {values.children &&
                values.children.map((child, index) => (
                    <div key={index}>
                        <hr />
                        {t("form101.child")} {index + 1}{"  "}
                        <button
                            type="button"
                            className="btn btn-sm ml-2 mb-2 action-btn"
                            onClick={() => {
                                const newChildren = [...values.children];
                                newChildren.splice(index, 1);
                                setFieldValue("children", newChildren);
                            }}
                        >
                            <FaRegTrashCan />
                        </button>
                        <div className="row">
                            <div className="col">
                                <TextField
                                    name={`children[${index}].firstName`}
                                    label={t("form101.label_firstName")}
                                    value={child.firstName}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].firstName &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].firstName
                                    }
                                    required
                                />
                            </div>
                            <div className="col">
                                <TextField
                                    name={`children[${index}].IdNumber`}
                                    label={t("form101.id_num")}
                                    value={child.IdNumber}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].IdNumber &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].IdNumber
                                    }
                                    required
                                />
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-md-6">
                                <DateField
                                    name={`children[${index}].Dob`}
                                    label={t("form101.dob")}
                                    value={child.Dob}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].Dob &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].Dob
                                    }
                                    required={true}
                                />
                            </div>
                        </div>
                        <div className="row">
                            <div className="ml-3">
                                <CheckBox
                                    name={`children[${index}].inCustody`}
                                    label={t("form101.child_custody")}
                                    value={child.inCustody}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.inCustody}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].inCustody &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].inCustody
                                    }
                                />
                            </div>
                            <div className="ml-4">
                                <CheckBox
                                    name={`children[${index}].haveChildAllowance`}
                                    label={t("form101.child_insurance")}
                                    value={child.haveChildAllowance}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.haveChildAllowance}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index]
                                            .haveChildAllowance &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index]
                                            .haveChildAllowance
                                    }
                                />
                            </div>
                        </div>
                    </div>
                ))}
            <button
                type="button"
                className="btn button add mb-3 mt-3 action-btn"
                onClick={() => {
                    setFieldValue("children", [
                        ...values.children,
                        childrenInitial,
                    ]);
                }}
            >
                {t("form101.button_addChild")}
            </button>
        </div>
    );
};

export default ChildrenDetails;
